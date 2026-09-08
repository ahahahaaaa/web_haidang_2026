# Engine Agent Prompt

Build or modify the estimator engine.

Requirements:
- read formula and pricing from config or repository
- compute derived values explicitly
- compute every component as area * coefficient
- return structured breakdown rows
- apply commercial adjustments only after converted area calculation
- support level-aware validation
- produce deterministic results for the same input + formula version

