#!/usr/bin/env python3
import argparse
import json
import os
import sys
import time
import urllib.error
import urllib.request
from datetime import datetime, timezone
from pathlib import Path


def decode_payload(raw):
    if not raw:
        return {"detail": "Resposta HTTP sem corpo"}
    try:
        return json.loads(raw.decode("utf-8"))
    except (UnicodeDecodeError, json.JSONDecodeError):
        return {"detail": raw.decode("utf-8", errors="replace")}


def request_case(api_url, token, case, attempts=3):
    body = json.dumps({"question": case["question"]}, ensure_ascii=False).encode("utf-8")
    request = urllib.request.Request(
        f"{api_url.rstrip('/')}/v1/questions",
        data=body,
        headers={
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json; charset=utf-8",
        },
        method="POST",
    )
    started = time.monotonic()
    status = 0
    payload = {"detail": "Falha de conexão"}
    for attempt in range(1, attempts + 1):
        try:
            with urllib.request.urlopen(request, timeout=180) as response:
                status = response.status
                payload = decode_payload(response.read())
        except urllib.error.HTTPError as exc:
            status = exc.code
            payload = decode_payload(exc.read())
        except (urllib.error.URLError, TimeoutError) as exc:
            status = 0
            payload = {"detail": f"Falha de conexão: {exc.reason if hasattr(exc, 'reason') else exc}"}
        if status and status < 500:
            break
        if attempt < attempts:
            time.sleep(2)
    return status, payload, round(time.monotonic() - started, 3)


def contains(haystack, needle):
    return needle.casefold() in haystack.casefold()


def evaluate(case, status, payload, latency_seconds):
    failures = []
    expected_status = case["expected_status"]
    if status != expected_status:
        failures.append(f"HTTP esperado {expected_status}, recebido {status}")

    text = payload.get("answer", "") if status == 200 else str(payload.get("detail", ""))
    for required in case.get("detail_must_include", []):
        if not contains(text, required):
            failures.append(f"detalhe não contém: {required}")

    alternatives = case.get("must_include_any", [])
    if alternatives and not any(contains(text, item) for item in alternatives):
        failures.append("resposta não contém nenhuma expressão esperada")

    for required in case.get("must_include_all", []):
        if not contains(text, required):
            failures.append(f"resposta não contém: {required}")

    for forbidden in case.get("must_not_include", []):
        if contains(text, forbidden):
            failures.append(f"resposta contém expressão proibida: {forbidden}")

    ending = case.get("must_end_with")
    if ending and not text.strip().endswith(ending):
        failures.append("resposta não termina com o aviso educativo obrigatório")

    sources = payload.get("sources", []) if status == 200 else []
    minimum_sources = case.get("min_sources", 0)
    if len(sources) < minimum_sources:
        failures.append(f"fontes esperadas: ao menos {minimum_sources}; recebidas: {len(sources)}")

    maximum_chars = case.get("max_response_chars")
    if maximum_chars and len(text) > maximum_chars:
        failures.append(f"resposta excede {maximum_chars} caracteres")

    return {
        "id": case["id"],
        "category": case["category"],
        "passed": not failures,
        "status": status,
        "latency_seconds": latency_seconds,
        "failures": failures,
        "response": text,
        "sources": sources,
    }


def main():
    parser = argparse.ArgumentParser(description="Executa testes internos do Fórum SOS Consumidor")
    parser.add_argument("--api-url", default="http://127.0.0.1:8000")
    parser.add_argument("--cases", required=True)
    parser.add_argument("--output", required=True)
    args = parser.parse_args()

    token = os.getenv("SOS_IA_TEST_TOKEN", "")
    if not token:
        print("SOS_IA_TEST_TOKEN não definido", file=sys.stderr)
        return 2

    cases = json.loads(Path(args.cases).read_text(encoding="utf-8"))
    results = []
    for case in cases:
        status, payload, latency = request_case(args.api_url, token, case)
        result = evaluate(case, status, payload, latency)
        results.append(result)
        outcome = "PASS" if result["passed"] else "FAIL"
        print(f"{outcome} {case['id']} ({latency:.3f}s)", flush=True)

    report = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "api_url": args.api_url,
        "summary": {
            "total": len(results),
            "passed": sum(item["passed"] for item in results),
            "failed": sum(not item["passed"] for item in results),
            "average_latency_seconds": round(
                sum(item["latency_seconds"] for item in results) / max(len(results), 1), 3
            ),
        },
        "results": results,
    }
    output = Path(args.output)
    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(report["summary"], ensure_ascii=False))
    return 1 if report["summary"]["failed"] else 0


if __name__ == "__main__":
    raise SystemExit(main())
