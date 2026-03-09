from __future__ import annotations

from datetime import datetime

import MetaTrader5 as mt5
import pandas as pd

from .config import BotConfig, TIMEFRAME_MAP


def initialize_mt5(cfg: BotConfig) -> None:
    if not mt5.initialize(login=cfg.login, password=cfg.password, server=cfg.server):
        raise RuntimeError(f"MT5 initialize failed: {mt5.last_error()}")


def shutdown_mt5() -> None:
    mt5.shutdown()


def fetch_rates(symbol: str, timeframe: str, bars: int) -> pd.DataFrame:
    mt5_tf = _to_mt5_timeframe(timeframe)
    rates = mt5.copy_rates_from_pos(symbol, mt5_tf, 0, bars)
    if rates is None:
        raise RuntimeError(f"copy_rates_from_pos failed: {mt5.last_error()}")

    df = pd.DataFrame(rates)
    if df.empty:
        raise RuntimeError("No rates returned for requested symbol/timeframe")

    df["time"] = pd.to_datetime(df["time"], unit="s", utc=True)
    return df


def fetch_spread_points(symbol: str) -> int:
    tick = mt5.symbol_info_tick(symbol)
    info = mt5.symbol_info(symbol)
    if not tick or not info:
        raise RuntimeError(f"symbol_info/symbol_info_tick failed for {symbol}: {mt5.last_error()}")
    return int(round((tick.ask - tick.bid) / info.point))


def latest_bar_time(df: pd.DataFrame) -> datetime:
    return df.iloc[-1]["time"].to_pydatetime()


def _to_mt5_timeframe(tf: str) -> int:
    minutes = TIMEFRAME_MAP[tf]
    mapping = {
        1: mt5.TIMEFRAME_M1,
        5: mt5.TIMEFRAME_M5,
        15: mt5.TIMEFRAME_M15,
        30: mt5.TIMEFRAME_M30,
        60: mt5.TIMEFRAME_H1,
        240: mt5.TIMEFRAME_H4,
        1440: mt5.TIMEFRAME_D1,
    }
    return mapping[minutes]
