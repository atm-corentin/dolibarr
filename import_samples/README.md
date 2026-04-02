# Import samples for CliChaumeil product cost breakdown

These files document the supported import contract for the cost breakdown on simple products and simple services.

## Percentage convention

- `10` means `10%`
- Rates are stored and compared with 4 decimals
- Amounts are normalized with 4 decimals

## Official columns

Use only these official columns for the cost breakdown:

- `extra.clichaumeil_pa_support`
- `extra.clichaumeil_pa_sav`
- `extra.clichaumeil_pa_machine`
- `extra.clichaumeil_pa_encre`
- `extra.clichaumeil_pa_mo`
- `extra.clichaumeil_conditionnement_percent`
- `extra.clichaumeil_transport_percent`
- `extra.clichaumeil_fg_percent`

## Import behavior

- Absent optional column: the existing database value is kept unchanged.
- Present empty amount column: the empty value is imported, then normalized to `0` by the calculator.
- Present empty `conditionnement` or `transport` column: the empty value is imported, then normalized to `0`.
- Present empty `FG%` column: if `CLICHAUMEIL_DEFAULT_OVERHEAD_RATE` is configured, that default value is injected and persisted. Otherwise the import is rejected.
- Absent `FG%` column: if `CLICHAUMEIL_DEFAULT_OVERHEAD_RATE` is configured, that default value is injected and persisted. Otherwise the import is rejected.

## Files

### `products_cost_breakdown_sample.csv`

Nominal create sample with:

- simple products
- one simple service
- one row with empty `FG%` to validate the default-overhead fallback during import

Expected behavior:

- rows with explicit `FG%` calculate `PA frais généraux` and `cost_price` directly
- the service row is handled like a simple service
- the row with empty `FG%` succeeds only if `CLICHAUMEIL_DEFAULT_OVERHEAD_RATE` is configured

### `products_cost_breakdown_update_sample.csv`

Full-column update sample with:

- explicit value changes
- empty `conditionnement` and/or `transport` values

Expected behavior:

- empty optional rate cells are imported as empty values, then normalized to `0`
- all listed official columns are overwritten because they are present in the file

### `products_cost_breakdown_update_partial_sample.csv`

Partial update sample with reduced headers.

Expected behavior:

- only the columns present in the file are modified
- all absent official columns keep their current database values
- the second row shows that an empty `FG%` falls back to `CLICHAUMEIL_DEFAULT_OVERHEAD_RATE` when configured, otherwise the import is rejected

### `products_cost_breakdown_missing_fg_without_default_sample.csv`

Diagnostic sample for the case where no `FG%` column is provided at all.

Expected behavior:

- if `CLICHAUMEIL_DEFAULT_OVERHEAD_RATE` is configured, the import succeeds and the default value is injected
- if `CLICHAUMEIL_DEFAULT_OVERHEAD_RATE` is empty, the import is rejected with a business error

## Formula reminder

- `baseCost = pa_support + pa_sav + pa_machine + pa_encre + pa_mo`
- `packagingAmount = baseCost * conditionnement_percent / 100`
- `transportAmount = baseCost * transport_percent / 100`
- `totalCosts = baseCost + packagingAmount + transportAmount`
- `paFg = totalCosts * fg_percent / 100`
- `costPrice = totalCosts + paFg`
