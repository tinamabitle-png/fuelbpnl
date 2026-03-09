from __future__ import annotations

import pandas as pd

from ..data_models import Signal
from .base import Strategy


class EmaRsiStrategy(Strategy):
    name = "ema_rsi"

    def analyze(self, df: pd.DataFrame) -> Signal:
        work = df.copy()
        work["ema_fast"] = work["close"].ewm(span=20).mean()
        work["ema_slow"] = work["close"].ewm(span=50).mean()

        delta = work["close"].diff()
        gain = delta.where(delta > 0, 0).rolling(14).mean()
        loss = (-delta.where(delta < 0, 0)).rolling(14).mean()
        rs = gain / loss.replace(0, 1e-9)
        work["rsi"] = 100 - (100 / (1 + rs))

        row = work.iloc[-1]
        if row["ema_fast"] > row["ema_slow"] and row["rsi"] < 65:
            return Signal(self.name, "buy", 0.62, "Trend up and RSI below overbought")
        if row["ema_fast"] < row["ema_slow"] and row["rsi"] > 35:
            return Signal(self.name, "sell", 0.62, "Trend down and RSI above oversold")
        return Signal(self.name, "flat", 0.5, "No clean EMA+RSI alignment")
