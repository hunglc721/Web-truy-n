# NLP / Search Evaluation Harness

Reproducible, offline evaluation for the ComicX Vietnamese preference parser and
the retrieval services it feeds. The harness exists to answer one question with
evidence:

> Did a change to Vietnamese NLP or retrieval actually improve the chatbot, and
> did it regress negation, combined filters, or unpublished-content hiding?

It is **not** a second NLP system. Parsing is scored against the production
`RuleBasedPreferenceParser`; retrieval is scored against the production
`SearchService` and `PreferenceRecommendationService`.

---

## 1. What is evaluated

| Layer | Production code used | How it is scored |
| --- | --- | --- |
| Intent | `PreferenceParser` input guard + `RuleBasedPreferenceParser` output | Labelled intent: `lookup`, `preferences`, `clarify`, `invalid` |
| Slots | `RuleBasedPreferenceParser` | Per-field set precision/recall/F1 and exact-set match |
| Negation / exclusion | `RuleBasedPreferenceParser` `exclude` list | Set accuracy + micro P/R/F1 |
| Status | `RuleBasedPreferenceParser` `status` | Accuracy, labelled accuracy, false positives |
| Retrieval | `SearchService::liveSearch`, `SearchService::advancedSearch`, `PreferenceRecommendationService::recommend` | P@K, R@K, micro P/R, empty-result accuracy, publication leakage, exclusion violations |

No model training, no external API calls, and no production-data changes are
involved. The command forces the `array` cache driver and sets
`ai.enabled=false`, so the rule-based path is exercised deterministically and
AI quotas/providers are never touched.

### Intent is an observable, not a classifier

The production parser does not emit an intent label. The harness derives it
without looking at the ground truth:

1. `invalid` when `PreferenceParser` rejects the input (`invalid_input`).
2. `preferences` when a parse succeeds and `needs_more_info === false`.
3. Otherwise `lookup` when the normalized query matches a catalog title,
   alt-title, author, or a description with at least two tokens; else `clarify`.

A parser exception or error is reported as the intent `error` and counts as an
intent miss. That mirrors how the recommendation flow branches today. It is a
metric-layer observable and is documented here so no one mistakes it for a
trained intent classifier.

---

## 2. Dataset

File:

```text
laravel-blade/tests/Fixtures/nlp/evaluation_dataset.json
```

One self-contained file with:

- `version`, `k` (retrieval cut-off; currently `8`)
- `corpus.genres` — the genre vocabulary used by the synthetic catalog
- `corpus.comics` — 14 synthetic comics, each with title, alt titles, author,
  description, status, genres, tags, view count, and `published_offset_days`
  (`null` = never published, `> 0` = scheduled in the future)
- `cases` — 41 labelled queries

Each case has:

```json
{
  "id": "negation-comedy-no-romance",
  "group": "negation",
  "query": "truyện hài nhưng không có tình cảm",
  "expected": {
    "intent": "preferences",
    "needs_more_info": false,
    "genres": ["Comedy"],
    "themes": [],
    "settings": [],
    "character_traits": [],
    "tones": ["Comedy"],
    "relationships": [],
    "status": null,
    "exclude": ["Romance"]
  },
  "search": {
    "engine": "recommendation",
    "relevant": ["hai_huoc"]
  }
}
```

`search` is optional. When present, `relevant` lists the only acceptable comic
keys at K; `expect_empty: true` is mandatory when there is no relevant result
and is how no-result / hidden-content cases are labelled. A long-input case uses
`query_fixture` (`repeat` + `times`) instead of embedding 4,001 characters.

Coverage groups (10):

| Group | Focus |
| --- | --- |
| `lookup` | title / alt-title / author / description lookups, including unpublished and unaccented coverage |
| `accented` | fully accented Vietnamese phrasing |
| `unaccented` | no-diacritic input (the P1 upgrade) |
| `colloquial` | slang, abbreviations (`main op`), synonyms |
| `taxonomy` | genre/tag/status vocabulary coverage |
| `negation` | `không`, `đừng`, `bỏ`, `loại bỏ`, exclusion-only |
| `multi` | combined genre + status + character + exclusion |
| `vague` | vague/no-result requests |
| `invalid` | empty and oversized input |
| `outside_taxonomy` | unknown words that must not invent filter values |

Validation happens before any database work and rejects: duplicate case IDs,
unknown comic keys, unknown statuses, values outside the corpus taxonomy,
`expect_empty` with relevant comics, searches without relevance labels, both or
neither of `query`/`query_fixture`, and out-of-range `k`.

---

## 3. Metrics and formulas

Implemented in `App\Services\Evaluation\EvaluationMetrics` and aggregated in
`NlpEvaluator`.

### Set metrics (slots, exclusions)

For each dimension, treating values as sets:

```text
tp = |predicted ∩ expected|
fp = |predicted \ expected|
fn = |expected \ predicted|

precision = tp / (tp + fp)
recall    = tp / (tp + fn)
F1        = 2PR / (P + R)
```

Undefined ground truth is never counted as correct:

- expected empty **and** predicted empty → precision/recall/F1 are `null`
  (excluded from means, printed as `n/a`);
- expected empty, predicted non-empty → `0.0` precision;
- expected non-empty, predicted empty → `0.0` recall;
- exact-set accuracy is order-insensitive but a one-sided empty set is a miss.

### Retrieval at K

```text
P@K = |returned@K ∩ relevant| / min(K, |returned@K|)
R@K = |returned@K ∩ relevant| / |relevant|
```

- `P@K` is `0.0` when relevance exists but nothing was returned.
- `P@K`/`R@K` are `null` when a case has no relevance labels
  (`expect_empty: true`), so no-result cases are scored by
  **empty-result accuracy** instead.
- `micro_precision = Σhits / Σ|returned|`, `micro_recall = Σhits / Σ|relevant|`.
- Rows returning comics outside the synthetic corpus are counted as false
  positives.
- **Publication leakage**: any returned comic whose `published_at` is null or in
  the future is listed per case.
- **Exclusion violations**: any returned comic carrying an excluded
  genre/tag/status is listed per case.

### Parser and error rates

```text
intent_accuracy          = correct intent labels / all cases
needs_more_info_accuracy = correct flags / scored cases that produced a flag
status_accuracy          = correct status / scored cases
status_labelled_accuracy = correct status / cases with a non-null status
exclusion_accuracy       = exact exclusion set match / scored cases
invalid_accuracy         = invalid inputs detected / invalid cases
clarification_rate       = cases where predicted needs_more_info = true / scored cases
fallback_rate            = parser source = "fallback" / cases with an AI/fallback source
error_rate               = cases whose evaluation threw or parser returned an error / all cases
```

`clarification_rate` is descriptive, not "higher is better", so it is excluded
from regression/improvement detection.

### Before/after comparison

`--baseline=head` loads
`laravel-blade/app/Services/AI/RuleBasedPreferenceParser.php` from `git show HEAD`,
rewrites its namespace into `App\Services\AI\EvaluationBaseline`, requires it
from a temporary file, and deletes that file. The same dataset, corpus, K, and
database transaction are used for both runs.

`NlpEvaluationReport` flattens both runs, computes raw deltas (`ratio` in
percentage points, `count` as an integer), and classifies group-level changes
with the metric's direction:

- higher is better: accuracy, precision, recall, F1, P@K, R@K, empty accuracy;
- lower is better: error rate, fallback rate, leakage cases, exclusion violations;
- neutral (never a regression): clarification rate, case counts.

A metric that is `null` on either side gets a `null` delta and can never be
reported as an improvement or a regression.

---

## 4. How to run

From `laravel-blade/`, against a throwaway database (the command opens a
transaction, seeds the synthetic catalog, and always rolls it back):

```bash
# 1. Prepare a disposable SQLite database
DB_CONNECTION=sqlite DB_DATABASE=/tmp/comicx-nlp-eval.sqlite \
  php artisan migrate:fresh --force

# 2. Evaluate the working tree
DB_CONNECTION=sqlite DB_DATABASE=/tmp/comicx-nlp-eval.sqlite CACHE_STORE=array \
  php artisan nlp:evaluate

# 3. Compare against git HEAD and write the full JSON report
DB_CONNECTION=sqlite DB_DATABASE=/tmp/comicx-nlp-eval.sqlite CACHE_STORE=array \
  php artisan nlp:evaluate --baseline=head --format=json \
  --output=../docs/nlp-evaluation-report.json
```

PowerShell equivalent:

```powershell
$db = "$env:TEMP\comicx-nlp-eval.sqlite"
$env:DB_CONNECTION = 'sqlite'; $env:DB_DATABASE = $db; $env:CACHE_STORE = 'array'
php artisan migrate:fresh --force
php artisan nlp:evaluate --baseline=head --format=json --output=../docs/nlp-evaluation-report.json
```

### Options

```text
--dataset=        Path to the labelled dataset JSON (default: tests/Fixtures/nlp/evaluation_dataset.json)
--group=*         Evaluate only these groups; repeatable
--k=              Override K (1..50)
--baseline=none   No comparison (default)
--baseline=head   Load the parser from git HEAD and compare on the same dataset
--format=table    Human-readable tables (default)
--format=json     Machine-readable report on stdout
--output=         Write the full JSON report to a path (relative to laravel-blade/)
--force           Required only in the production environment
```

Safety properties:

- refuses to run in `production` without `--force`;
- forces `cache.default=array` and `ai.enabled=false`, then restores both;
- seeds `nlp-eval-*` comics inside a transaction and rolls back even if the run
  throws;
- prints a warning instead of failing when the HEAD baseline cannot be loaded.

> `fallback_rate` is **100% by design** in this harness because the AI provider
> is disabled. It is reported to prove the offline path was used, not as a
> quality signal.

### Tests

```bash
cd laravel-blade
php artisan test tests/Unit/EvaluationMetricsTest.php \
  tests/Unit/NlpEvaluationDatasetTest.php \
  tests/Unit/NlpEvaluationReportTest.php \
  tests/Feature/NlpEvaluationHarnessTest.php
```

Covered:

- metric math (set PRF, P@K/R@K edge cases, means, exact-set accuracy, deltas,
  direction-aware regression detection, null ground truth);
- dataset loading/validation and required group coverage;
- full labelled parser accuracy, negation, multi-condition, exclusions;
- lookup retrieval and public-visibility rules (asserted explicitly);
- before/after comparison using a stub baseline (no assumed improvement);
- command transaction rollback, zero provider calls, AI config restoration,
  production guard;
- HEAD baseline loader namespace rewrite and execution via a `Process` fake.

---

## 5. Actual results

Run details:

| Field | Value |
| --- | --- |
| Date | 2026-10-26 |
| Git HEAD (baseline) | `1b23888` |
| App | Laravel 11.55.1, PHP 8.3.16 |
| Database | disposable SQLite |
| Cache | `array` |
| AI provider | disabled, 0 calls |
| Ranking policy | P4 full-match-first with partial fallback |
| Dataset | v1, 41 cases, 14 comics, 16 genres, K = 8 |
| Dataset SHA-256 | `8acc91f8d9cdeda8054e87788a535cd07b3aaf7f15cfc8a827dddf103d6387c7` |
| Full report | [`docs/nlp-evaluation-report.json`](nlp-evaluation-report.json) |

### P4 ranking policy: before vs after

P4 changed only the production service `PreferenceRecommendationService`. The
before row is the P3 working tree, captured on the same disposable database,
dataset, K, and cache configuration with
`php artisan nlp:evaluate --baseline=none --format=json`. The after row is the
current working tree and matches the `current` metrics in
`docs/nlp-evaluation-report.json`.

| Metric | P3 before P4 | P4 after | Delta |
| --- | ---: | ---: | ---: |
| P@K (pipeline slots) | 91.8% | 100.0% | +8.2 pp |
| R@K (pipeline slots) | 100.0% | 100.0% | 0.0 pp |
| Pipeline micro precision | 84.3% | 100.0% | +15.7 pp |
| Pipeline micro recall | 100.0% | 100.0% | 0.0 pp |
| P@K (oracle slots) | 89.4% | 100.0% | +10.6 pp |
| R@K (oracle slots) | 100.0% | 100.0% | 0.0 pp |
| Publication-leak cases | 0 | 0 | 0 |
| Exclusion-violation results | 0 | 0 | 0 |
| Retrieval failures | 4 | 0 | −4 |

**Policy.** Status, publication, and exclusions are hard constraints applied
before ranking. Genres and the `theme`/`setting`/`character`/`tone` tags are
positive requirements; every requested value must be present for a full match.
Relationships still have no positive weight (only their exclusions apply), so a
relationship-only request does not invent a requirement. Full matches always
rank first. When at least one full match exists, only full matches are returned.
Partial matches are a documented fallback used only when the full-match tier is
empty; they are ordered by the number of matched requested values, then the
weighted dimension score, then newest id. Every fallback row still satisfies
publication, status, and exclusions.

**Precision/recall impact.** The policy raises precision on multi-condition
queries by removing partial matches that used to dilute the top-K list. It
intentionally trades away padded recall: if a request has one full match and the
limit is five, the service returns one result instead of four weaker partials.
The dataset's `R@K` stays at 100% because its relevance labels are full matches;
it does not measure the discarded partials. The production trade-off is fewer
results for highly specific requests, not lower recall of the labelled full
matches. Status also became a hard filter in P4, so a story with the wrong status
can no longer appear as a high-scoring partial.

### Overall

`current` = working tree (P1 parser + P4 ranking). `HEAD` = `1b23888` (pre-P1
parser) running against the current retrieval layer.

| Metric | Current | HEAD | Delta |
| --- | ---: | ---: | ---: |
| Intent accuracy | 100.0% | 75.6% | +24.4 pp |
| Needs-more-info accuracy | 100.0% | 74.4% | +25.6 pp |
| Slot precision (micro) | 100.0% | 73.7% | +26.3 pp |
| Slot recall (micro) | 100.0% | 46.7% | +53.3 pp |
| Slot F1 (micro) | 100.0% | 57.1% | +42.9 pp |
| Slot exact-set match | 100.0% | 59.0% | +41.0 pp |
| Status accuracy | 100.0% | 94.9% | +5.1 pp |
| Status accuracy (labelled) | 100.0% | 66.7% | +33.3 pp |
| Status false positives | 0 | 0 | 0 |
| Exclusion accuracy | 100.0% | 79.5% | +20.5 pp |
| Exclusion precision | 100.0% | 100.0% | 0.0 pp |
| Exclusion recall | 100.0% | 38.5% | +61.5 pp |
| Exclusion F1 (micro) | 100.0% | 55.6% | +44.4 pp |
| Invalid-input accuracy | 100.0% | 100.0% | 0.0 pp |
| Clarification rate | 30.8% | 56.4% | −25.6 pp (neutral) |
| Fallback rate | 100.0% | n/a | — |
| Error rate | 0.0% | 0.0% | 0.0 pp |
| P@K (oracle slots) | 100.0% | 100.0% | 0.0 pp |
| R@K (oracle slots) | 100.0% | 100.0% | 0.0 pp |
| P@K (pipeline slots) | 100.0% | 58.5% | +41.5 pp |
| R@K (pipeline slots) | 100.0% | 67.7% | +32.3 pp |
| Pipeline micro precision | 100.0% | 79.2% | +20.8 pp |
| Pipeline micro recall | 100.0% | 71.2% | +28.8 pp |
| Empty-result accuracy | 100.0% | 100.0% | 0.0 pp |
| Publication-leak cases | 0 | 0 | 0 |
| Exclusion-violation results | 0 | 10 | −10 |

Reading notes:

- **Parser metrics are clean**: 41/41 intent labels, all slot fields, all
  exclusions, and every status label match the ground truth. No errors.
- **P4 removed every current retrieval failure.** The four P3 ranking failures
  now return only their full matches (see the next table), so oracle and
  pipeline P@K are both 100%.
- **Publication leakage and exclusion violations remain at zero.** The P3 fixes
  are preserved by the P4 hard-constraint ordering.
- **The HEAD baseline is a parser-only ablation**: the HEAD parser runs against
  the current `SearchService`/`PreferenceRecommendationService`. Its P@K is
  therefore not a "before P4" retrieval number. Use the P4 table above for the
  ranking comparison; use this table for parser quality.
- **No group-level regressions were detected against HEAD** (the
  `changes.regressions` array in the JSON report is empty; 78 group/metric
  improvements were recorded).

### Per group (current / HEAD)

`n/a` means the metric is undefined for that group (for example lookup cases
have no expected slots). HEAD is the parser ablation against the current
retrieval layer.

| Group | Cases | Intent | Slot F1 | Exclusion | P@K | R@K | Leak cases |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| lookup | 8 | 100.0 / 100.0 | n/a | 100.0 / 100.0 | 100.0 / 100.0 | 100.0 / 100.0 | 0 / 0 |
| accented | 4 | 100.0 / 100.0 | 100.0 / 75.0 | 100.0 / 75.0 | 100.0 / 85.0 | 100.0 / 100.0 | 0 / 0 |
| unaccented | 3 | 100.0 / 66.7 | 100.0 / 0.0 | 100.0 / 33.3 | 100.0 / 0.0 | 100.0 / 0.0 | 0 / 0 |
| colloquial | 6 | 100.0 / 16.7 | 100.0 / 20.0 | 100.0 / 83.3 | 100.0 / 10.0 | 100.0 / 20.0 | 0 / 0 |
| taxonomy | 6 | 100.0 / 66.7 | 100.0 / 80.0 | 100.0 / 100.0 | 100.0 / 66.7 | 100.0 / 66.7 | 0 / 0 |
| negation | 5 | 100.0 / 80.0 | 100.0 / 60.0 | 100.0 / 40.0 | 100.0 / 43.8 | 100.0 / 75.0 | 0 / 0 |
| multi | 2 | 100.0 / 100.0 | 100.0 / 90.9 | 100.0 / 50.0 | 100.0 / 75.0 | 100.0 / 100.0 | 0 / 0 |
| vague | 3 | 100.0 / 66.7 | 100.0 / 0.0 | 100.0 / 100.0 | n/a | n/a | 0 / 0 |
| invalid | 2 | 100.0 / 100.0 | n/a | n/a | n/a | n/a | 0 / 0 |
| outside_taxonomy | 2 | 100.0 / 100.0 | n/a | 100.0 / 100.0 | n/a | n/a | 0 / 0 |

P4 removed the remaining colloquial and multi P@K loss. HEAD's parser ablation
still shows the P1 parser gaps in `unaccented`, `colloquial`, and `negation`.

### Per-case retrieval failures

`diagnostics.retrieval_pipeline_failures` is empty after P4. The four cases that
failed in P3 now behave as follows:

| Case | P3 failure | P4 result |
| --- | --- | --- |
| `slang-tu-tien-xuyen-khong` | returned `vo_luyen` (Cultivation only) beside the full match | returns `isekai_cultivation` only |
| `slang-cold-smart` | returned `school_no_romance` and `trinh_tham` (Smart MC only) beside the full match | returns `system_revenge` only |
| `multi-fantasy-completed-op-no-harem` | returned 8 comics matching any one dimension; only `solo` matches all | returns `solo` only |
| `multi-school-comedy-no-romance` | returned `school_no_romance` (partial match) beside `hai_huoc` | returns `hai_huoc` only |

The three lookup/search findings from the original P3 run remain fixed and
covered by regression tests. The P4 regression test
`NlpEvaluationHarnessTest::test_parser_evaluation_preserves_negation_and_multiple_conditions`
asserts all four results above plus P@K = 1.0 so the OR-ranking bug cannot return
silently.

---

## 6. Findings and follow-ups

P3 recorded six findings. The P3 audit fixed #1 and #2; P4 fixed #3. The
remaining items are follow-ups.

1. **Fixed — unpublished comics leaked through search.**
   `SearchService::liveSearch` and `advancedSearch` built queries without a
   publication predicate. Both now apply `Comic::published()`
   (`published_at` not null and `<= now()`). Behavioral recommendation queries
   (`RecommendationService::forUser`, `forComic`, `forGuest`, and the fallback)
   received the same guard. Covered by `SearchServiceTest`, `SearchApiTest`,
   `RecommendationServiceTest`, and `NlpEvaluationHarnessTest`
   (`lookup-unpublished-hidden` now returns an empty result).

2. **Fixed — unaccented author and description lookups failed.**
   Author predicates now also match the author's ASCII slug, and advanced
   search matches a new `comics.description_normalized` column kept in sync by
   `Comic::saving`. A migration backfills existing rows. Covered by
   `test_live_search_matches_author_without_accents`,
   `test_advanced_search_matches_description_without_accents`, and the
   `lookup-author-unaccented` / `lookup-description-unaccented` P3 cases.

3. **Fixed — recommendation now ranks full matches first (P4).**
   `PreferenceRecommendationService` previously ORed every dimension and scored
   each matched dimension, so a status-only or single-tag match could sit beside
   a full match and dilute P@K. P4 applies status, publication, and exclusions as
   hard SQL constraints, requires every requested genre/tag value for a full
   match, ranks by matched value count, and returns partial matches only through
   the documented fallback when no full match exists. `multi` P@K moved 31.3% →
   100% and `colloquial` 76.7% → 100% (pipeline P@K 91.8% → 100%, micro
   precision 84.3% → 100%). Covered by new cases in
   `PreferenceRecommendationTest` (single condition, multi condition, hard
   status, bounded partial fallback, exclusion/publication) and strengthened
   assertions in `NlpEvaluationHarnessTest`.

4. **Resolved — empty-result accuracy is 100%** after the publication fix.

5. **Low — fallback rate is 100% by design** in this offline harness. Evaluate
   the AI path separately with a mocked provider and a fixed fixture before
   making claims about provider quality.

6. **Low — intent is a derived observable.** A future real intent classifier (or
   an explicit parser field) should replace `looksLikeLookup`; until then,
   intent numbers are comparable only within this harness.

---

## 7. Limitations

- The corpus is synthetic (14 comics) and small. It is designed to exercise
  specific mechanisms, not to estimate production traffic accuracy. Treat the
  absolute numbers as regression signals, not as user-facing quality.
- 41 cases is enough for targeted regression coverage but too small for
  statistically robust per-group estimates; one case can move a group by
  tens of points.
- Only K = 8 is reported. The command supports `--k`, but no sensitivity
  analysis is included.
- Relevance labels are binary and exhaustive at K; there is no graded
  relevance.
- The P4 full-match policy can return fewer than K results when only a few full
  matches exist. `R@K` on this dataset does not measure the partial matches that
  were intentionally discarded; the precision/recall trade-off is documented in
  section 5 rather than inferred from the labels.
- P4 makes status a hard filter. This is the requested product behaviour, but it
  is stricter than the previous partial-status ranking and is covered by a
  dedicated regression test.
- The ranking SQL adds one correlated `EXISTS` per requested value. The
  1,200-candidate hydration test covers the common multi-condition shape, but
  queries with very many tag values were not benchmarked.
- The dataset is authored by the same team that wrote the parser, so it can
  share blind spots with the implementation.
- Only the deterministic rule-based path is measured. The AI prompt/schema,
  quota, cache, and provider latency are out of scope.
- The HEAD baseline is loaded from the current repository's `HEAD`. In CI or a
  shallow clone without `HEAD` history, `--baseline=head` may be unavailable;
  the command warns and continues instead of failing.
- `published_offset_days` is evaluated relative to the run date, so a saved
  report is only reproducible while its corpus offsets remain in the past or
  future as intended (offsets are large enough not to flip for this suite).

---

## 8. Extending the dataset

1. Add the comic(s) to `corpus.comics` with a unique `key`, valid genre/status
   values, and `published_offset_days` (`null` or `> 0` for hidden content).
2. Add a case with a unique `id` and a `group`.
3. Fill every `expected` slot explicitly (`[]` when none) and list the exact
   exclusions.
4. If retrieval should be scored, set `search.engine` and either `relevant`
   comic keys or `expect_empty: true`.
5. Run the unit tests first; validation failures name the exact offending path
   (for example `cases[19].expected.relationships`).
6. Run `php artisan nlp:evaluate --group=<new-group> --baseline=head` and confirm
   the case behaves as intended before adding assertions for it.

Never "fix" a failing case by loosening its labels. If the production behaviour
is wrong, fix the production code or record the gap explicitly as a known
finding with a regression test that asserts the current (wrong) behaviour until
it is fixed.
