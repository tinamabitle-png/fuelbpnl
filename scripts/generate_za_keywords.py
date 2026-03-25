#!/usr/bin/env python3
"""
Generate a South Africa keyword bank for:
- mobility / e-hailing / taxi
- food & grocery delivery
- fuel / vouchers / fleet / stations

Output: resources/seo/keywords_za_mobility_fuel.csv

Note: Modern SEO doesn't use <meta name="keywords">. This file is meant for
landing page planning, headings, FAQs, internal linking, and ad groups.
"""

from __future__ import annotations

import csv
import os
from pathlib import Path


def uniq(seq):
    seen = set()
    out = []
    for x in seq:
        x = " ".join(str(x).split()).strip()
        if not x:
            continue
        k = x.lower()
        if k in seen:
            continue
        seen.add(k)
        out.append(x)
    return out


def main() -> int:
    root = Path(__file__).resolve().parents[1]
    out_dir = root / "resources" / "seo"
    out_dir.mkdir(parents=True, exist_ok=True)
    out_path = out_dir / "keywords_za_mobility_fuel.csv"

    # Major places (mix of provinces, metros, big towns).
    places = uniq(
        [
            "South Africa",
            "Gauteng",
            "Johannesburg",
            "Sandton",
            "Midrand",
            "Soweto",
            "Pretoria",
            "Centurion",
            "Tshwane",
            "Ekurhuleni",
            "Kempton Park",
            "Boksburg",
            "Benoni",
            "Springs",
            "Vanderbijlpark",
            "Vereeniging",
            "KwaZulu-Natal",
            "Durban",
            "Umhlanga",
            "Pinetown",
            "Pietermaritzburg",
            "Richards Bay",
            "Mpumalanga",
            "Nelspruit",
            "Mbombela",
            "Witbank",
            "eMalahleni",
            "Limpopo",
            "Polokwane",
            "Thohoyandou",
            "Makhado",
            "North West",
            "Rustenburg",
            "Mahikeng",
            "Free State",
            "Bloemfontein",
            "Welkom",
            "Eastern Cape",
            "Gqeberha",
            "Port Elizabeth",
            "East London",
            "Mthatha",
            "Western Cape",
            "Cape Town",
            "Bellville",
            "Stellenbosch",
            "Paarl",
            "George",
            "Knysna",
            "Northern Cape",
            "Kimberley",
        ]
    )

    # Platforms and categories (keep generic; avoid claiming affiliation).
    platforms = uniq(
        [
            "Uber",
            "Bolt",
            "inDrive",
            "Mr D",
            "Uber Eats",
            "Checkers Sixty60",
            "takealot",
            "local taxi",
            "e-hailing",
            "food delivery",
            "grocery delivery",
            "parcel delivery",
            "courier",
        ]
    )

    # Core terms. We'll combine these with intents + places to get long-tail.
    cores = uniq(
        [
            # Mobility / taxi / e-hailing
            "e-hailing driver finance",
            "ride-hailing driver finance",
            "taxi driver fuel credit",
            "minibus taxi fuel voucher",
            "driver fuel advance",
            "driver fuel loan",
            "fuel for drivers buy now pay later",
            "fuel voucher for drivers",
            "fleet fuel voucher",
            "fleet fuel financing",
            "fuel voucher redemption",
            "voucher validation at fuel station",
            "station voucher redemption system",
            "fuel voucher fraud prevention",
            "fuel voucher POS integration",
            "driver wallet voucher",
            "voucher settlement to bank",
            "fuel settlement reconciliation",
            "fleet spend controls fuel",
            "driver spend controls",
            "fuel voucher audit trail",
            "fuel voucher reporting",
            "merchant voucher settlement",
            "merchant fuel voucher payout",
            "fuel station settlement software",
            "fuel station voucher scanning",
            "voucher QR code fuel",
            "voucher OTP fuel",
            "voucher pin redemption",
            "voucher risk controls",
            "voucher approval workflow",
            "credit controls for fuel",
            "driver credit limit fuel",
            "high credit limit fuel",
            # Delivery
            "delivery driver fuel credit",
            "food delivery fuel voucher",
            "grocery delivery fuel voucher",
            "courier fuel voucher",
            "last mile delivery fuel finance",
            "delivery fleet fuel controls",
            # General fuel
            "fuel credit South Africa",
            "fuel finance South Africa",
            "fuel buy now pay later",
            "fuel BNPL for fleets",
            "fuel vouchers South Africa",
            "fuel voucher management",
            "fuel voucher app",
            "fuel voucher platform",
            "fuel voucher API",
        ]
    )

    # Intent modifiers.
    intents = uniq(
        [
            "near me",
            "in {place}",
            "for {platform} drivers",
            "for taxi drivers",
            "for delivery drivers",
            "for fleet managers",
            "for fuel stations",
            "how it works",
            "pricing",
            "apply",
            "sign up",
            "register",
            "login",
            "contact",
            "support",
            "requirements",
            "documents",
            "verification",
            "instant approval",
            "same day",
            "weekly settlement",
            "bank settlement",
            "reconciliation",
            "audit",
            "compliance",
        ]
    )

    # Build keyword list with controlled explosion to exceed 1000.
    keywords: list[tuple[str, str]] = []

    def add(category: str, kw: str):
        keywords.append((category, kw))

    # Base cores.
    for c in cores:
        add("core", c)

    # Core + place.
    for c in cores:
        for p in places:
            add("local", f"{c} {p}")

    # Core + intent patterns.
    for c in cores:
        for i in intents:
            if "{place}" in i:
                # Only use a subset of places to avoid massive file sizes.
                for p in places[:25]:
                    add("intent", f"{c} {i.format(place=p)}")
            else:
                add("intent", f"{c} {i}")

    # Platform combinations.
    for p in platforms:
        add("platform", f"fuel voucher for {p} drivers")
        add("platform", f"{p} driver fuel credit")
        add("platform", f"{p} driver fuel finance South Africa")
        add("platform", f"{p} driver fuel voucher {places[0]}")

    # Taxi-specific long-tail.
    taxi_phrases = uniq(
        [
            "minibus taxi fuel voucher",
            "taxi association fuel credit",
            "rank-to-rank fuel financing",
            "taxi fleet fuel controls",
            "cashless fuel for taxis",
            "voucher redemption for taxis",
        ]
    )
    for t in taxi_phrases:
        add("taxi", t)
        for p in places[:30]:
            add("taxi_local", f"{t} {p}")

    # Food delivery specific.
    delivery_phrases = uniq(
        [
            "food delivery fuel credit",
            "grocery delivery fuel credit",
            "delivery driver fuel vouchers",
            "delivery driver fuel financing",
            "courier fuel credit",
            "last mile fuel voucher",
        ]
    )
    for d in delivery_phrases:
        add("delivery", d)
        for p in places[:30]:
            add("delivery_local", f"{d} {p}")

    # Merchant/station specific.
    merchant_phrases = uniq(
        [
            "fuel station voucher redemption",
            "fuel station settlement",
            "petrol station voucher system",
            "forecourt voucher validation",
            "kiosk voucher split fuel and shop",
            "voucher redemption reporting for stations",
        ]
    )
    for m in merchant_phrases:
        add("merchant", m)
        for p in places[:25]:
            add("merchant_local", f"{m} {p}")

    # Deduplicate + cap but ensure >= 1000.
    keywords = [(c, k) for (c, k) in keywords if k.strip()]
    uniq_rows = []
    seen = set()
    for cat, kw in keywords:
        key = kw.lower()
        if key in seen:
            continue
        seen.add(key)
        uniq_rows.append((cat, kw))

    # If the list somehow ends up too small, add generic expansions.
    if len(uniq_rows) < 1000:
        for p in places:
            for base in [
                "fuel voucher",
                "fuel credit",
                "fuel financing",
                "fleet fuel management",
                "driver fuel credit",
                "delivery driver fuel credit",
            ]:
                uniq_rows.append(("extra", f"{base} {p}"))
        # Re-dedupe
        final = []
        seen = set()
        for cat, kw in uniq_rows:
            key = kw.lower()
            if key in seen:
                continue
            seen.add(key)
            final.append((cat, kw))
        uniq_rows = final

    # Write CSV.
    with out_path.open("w", newline="", encoding="utf-8") as f:
        w = csv.writer(f)
        w.writerow(["category", "keyword"])
        w.writerows(uniq_rows)

    print(f"Wrote {len(uniq_rows)} keywords -> {out_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

