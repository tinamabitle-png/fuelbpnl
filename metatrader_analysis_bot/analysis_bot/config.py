from __future__ import annotations

import os
from dataclasses import dataclass

from dotenv import load_dotenv


@dataclass(frozen=True)
class BotConfig:
    login: int
    password: str
    server: str
    symbol: str
    timeframe: str
    bars: int
    poll_seconds: int
    dry_run: bool
    risk_per_trade_pct: float
    max_spread_points: int


TIMEFRAME_MAP = {
    "M1": 1,
    "M5": 5,
    "M15": 15,
    "M30": 30,
    "H1": 60,
    "H4": 240,
    "D1": 1440,
}


def _as_bool(value: str | None, default: bool) -> bool:
    if value is None:
        return default
    return value.strip().lower() in {"1", "true", "yes", "on"}


def load_config() -> BotConfig:
    load_dotenv()
    timeframe = os.getenv("BOT_TIMEFRAME", "M15").upper()
    if timeframe not in TIMEFRAME_MAP:
        allowed = ", ".join(sorted(TIMEFRAME_MAP.keys()))
        raise ValueError(f"Unsupported BOT_TIMEFRAME '{timeframe}'. Allowed: {allowed}")

    return BotConfig(
        login=int(os.getenv("MT5_LOGIN", "0")),
        password=os.getenv("MT5_PASSWORD", ""),
        server=os.getenv("MT5_SERVER", ""),
        symbol=os.getenv("BOT_SYMBOL", "EURUSD"),
        timeframe=timeframe,
        bars=int(os.getenv("BOT_BARS", "300")),
        poll_seconds=int(os.getenv("BOT_POLL_SECONDS", "60")),
        dry_run=_as_bool(os.getenv("BOT_DRY_RUN"), True),
        risk_per_trade_pct=float(os.getenv("BOT_RISK_PER_TRADE_PCT", "0.5")),
        max_spread_points=int(os.getenv("BOT_MAX_SPREAD_POINTS", "35")),
    )
