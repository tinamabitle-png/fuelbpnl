from __future__ import annotations

from dataclasses import dataclass
from datetime import datetime
from typing import Literal

SignalSide = Literal["buy", "sell", "flat"]


@dataclass(frozen=True)
class MarketSnapshot:
    symbol: str
    timeframe: str
    bars: int
    as_of: datetime


@dataclass(frozen=True)
class Signal:
    strategy: str
    side: SignalSide
    confidence: float
    reason: str
