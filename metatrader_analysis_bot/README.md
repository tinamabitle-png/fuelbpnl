# MetaTrader Analysis Bot (Separate Scaffold)

This is a **separate** bot scaffold focused on **market analysis + signal generation**, not mandatory trade execution.

## Goal

- Connect to MT5
- Pull market candles
- Evaluate multiple strategies
- Output structured trade ideas/signals
- Keep execution optional (`BOT_DRY_RUN=true` by default)

## Language Choices (Clear Guidance)

1. **Python (this scaffold)**
- Best for fast iteration, research, backtesting, and strategy experimentation.
- Rich ecosystem: `pandas`, stats/ML libraries, notebooks.
- Works well as the analysis engine even when execution is moved elsewhere.

2. **MQL5 (inside MetaTrader terminal)**
- Best for low-latency order execution and native MT5 integration.
- Strong choice when strategy is stable and you want broker-terminal tight coupling.
- Harder to do advanced data science workflows compared with Python.

3. **C# (.NET)**
- Good for robust multi-service architecture and strong typing.
- Useful when you need enterprise tooling and high-throughput backend services.
- Less convenient than Python for quant prototyping.

4. **Node.js/TypeScript**
- Useful for event-driven orchestration and API/web dashboard integrations.
- Reasonable for control-plane services; weaker than Python for quant analysis.

Recommended split for most teams:
- **Python for analysis + model logic**
- **MQL5 (or a thin execution adapter) for order placement**

## Potential Strategies

Included examples:
- **EMA + RSI trend filter**: trend-following with momentum confirmation.
- **Donchian breakout**: captures range expansion and trend starts.
- **Bollinger mean reversion**: fade extremes back to mean in range-bound markets.

Additional strategies to add next:
- Session-based momentum (London/NY open windows)
- Multi-timeframe confirmation (e.g., H1 trend + M15 entries)
- ATR volatility regime filter
- News blackout filter (avoid high-impact events)
- Pair correlation / relative strength rotation
- ML classifier for signal quality scoring

## Layout

```text
metatrader_analysis_bot/
  main.py
  requirements.txt
  .env.example
  analysis_bot/
    config.py
    data_models.py
    mt5_client.py
    runner.py
    strategies/
      base.py
      ema_rsi.py
      breakout.py
      mean_reversion.py
```

## Setup

```bash
cd metatrader_analysis_bot
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
cp .env.example .env
```

## Run

One cycle:

```bash
python main.py --once
```

Continuous polling:

```bash
python main.py
```

## Notes

- Keep `BOT_DRY_RUN=true` until strategy behavior is validated on demo data/accounts.
- Execution hook is intentionally left explicit in `analysis_bot/runner.py`.
- This scaffold avoids changing your existing `metatrader_bot` module.
