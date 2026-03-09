from __future__ import annotations

from ..data_models import Signal
from .base import Strategy


class BollingerMeanReversionStrategy(Strategy):
    name = "bollinger_mean_reversion"

    def analyze(self, df):
        period = 20
        if len(df) < period:
            return Signal(self.name, "flat", 0.4, "Insufficient data")

        mid = df["close"].rolling(period).mean().iloc[-1]
        std = df["close"].rolling(period).std().iloc[-1]
        upper = mid + (2 * std)
        lower = mid - (2 * std)
        close = df["close"].iloc[-1]

        if close < lower:
            return Signal(self.name, "buy", 0.58, "Price below lower Bollinger band")
        if close > upper:
            return Signal(self.name, "sell", 0.58, "Price above upper Bollinger band")
        return Signal(self.name, "flat", 0.5, "Price within Bollinger bands")
