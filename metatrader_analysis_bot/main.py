from __future__ import annotations

import argparse

from analysis_bot.config import load_config
from analysis_bot.mt5_client import initialize_mt5, shutdown_mt5
from analysis_bot.runner import loop, print_signal_report, run_once
from analysis_bot.strategies.breakout import DonchianBreakoutStrategy
from analysis_bot.strategies.ema_rsi import EmaRsiStrategy
from analysis_bot.strategies.mean_reversion import BollingerMeanReversionStrategy


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="MetaTrader analysis bot scaffold")
    parser.add_argument("--once", action="store_true", help="Run one analysis cycle and exit")
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    cfg = load_config()

    strategies = [
        EmaRsiStrategy(),
        DonchianBreakoutStrategy(),
        BollingerMeanReversionStrategy(),
    ]

    initialize_mt5(cfg)
    try:
        if args.once:
            snapshot, signals, spread = run_once(cfg, strategies)
            print_signal_report(cfg, snapshot, signals, spread)
            return
        loop(cfg, strategies)
    finally:
        shutdown_mt5()


if __name__ == "__main__":
    main()
