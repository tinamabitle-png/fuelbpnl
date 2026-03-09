from __future__ import annotations

from abc import ABC, abstractmethod

import pandas as pd

from ..data_models import Signal


class Strategy(ABC):
    name: str

    @abstractmethod
    def analyze(self, df: pd.DataFrame) -> Signal:
        raise NotImplementedError
