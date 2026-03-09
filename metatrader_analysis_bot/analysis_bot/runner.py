from __future__ import annotations

import time
from typing import Iterable

from .config import BotConfig
from .data_models import MarketSnapshot, Signal
from .mt5_client import fetch_rates, fetch_spread_points, latest_bar_time
from .strategies.base import Strategy


def run_once(cfg: BotConfig, strategies: Iterable[Strategy]) -> tuple[MarketSnapshot, list[Signal], int]:
    df = fetch_rates(cfg.symbol, cfg.timeframe, cfg.bars)
    spread_points = fetch_spread_points(cfg.symbol)
    snapshot = MarketSnapshot(cfg.symbol, cfg.timeframe, len(df), latest_bar_time(df))
    signals = [s.analyze(df) for s in strategies]
    return snapshot, signals, spread_points


def loop(cfg: BotConfig, strategies: Iterable[Strategy]) -> None:
    while True:
        snapshot, signals, spread = run_once(cfg, strategies)
        print_signal_report(cfg, snapshot, signals, spread)
        time.sleep(cfg.poll_seconds)


def print_signal_report(cfg: BotConfig, snapshot: MarketSnapshot, signals: list[Signal], spread_points: int) -> None:
    print(
        f"[{snapshot.as_of.isoformat()}] {snapshot.symbol} {snapshot.timeframe} "
        f"bars={snapshot.bars} spread={spread_points}pts"
    )

    spread_ok = spread_points <= cfg.max_spread_points
    print(f"spread_check={spread_ok} (max={cfg.max_spread_points})")

    for s in signals:
        print(f"- {s.strategy}: side={s.side} confidence={s.confidence:.2f} reason=\"{s.reason}\"")

    if cfg.dry_run:
        print("action=analysis_only (BOT_DRY_RUN=true)")
    else:
        print("action=execution_enabled (hook your order-routing module here)")

    print()
