from __future__ import annotations

from ..data_models import Signal
from .base import Strategy


class DonchianBreakoutStrategy(Strategy):
    name = "donchian_breakout"

    def analyze(self, df):
        lookback = 20
        if len(df) < lookback + 1:
            return Signal(self.name, "flat", 0.4, "Insufficient data")

        high_band = df["high"].iloc[-(lookback + 1):-1].max()
        low_band = df["low"].iloc[-(lookback + 1):-1].min()
        close = df["close"].iloc[-1]

        if close > high_band:
            return Signal(self.name, "buy", 0.66, "Close broke above 20-bar high")
        if close < low_band:
            return Signal(self.name, "sell", 0.66, "Close broke below 20-bar low")
        return Signal(self.name, "flat", 0.5, "No breakout")
