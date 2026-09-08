# Frontsite Agent Prompt

Build or modify the customer-facing construction cost estimator page.

Requirements:
- render fields by level profile
- keep formula logic out of blade/vue component when possible
- call estimator engine/service for calculation
- show breakdown rows and pricing packages
- preserve formula_version and raw estimate result for lead capture
- support responsive layout and clear grouping
- do not hard-code coefficients already present in formula.json

