# API Specification

## 1. Preview estimate

`POST /api/estimator/preview`

### Request
```json
{
  "level": "level_1",
  "formula_set": "default",
  "price_set": "default",
  "input": {}
}
```

### Response
```json
{
  "level": "level_1",
  "formula_version": "v1",
  "price_version": "v1",
  "input": {},
  "derived": {
    "base_area": 0
  },
  "components": [],
  "totals": {
    "converted_area": 0
  },
  "pricing": {
    "packages": []
  }
}
```

## 2. Save lead request

`POST /api/estimator/requests`

### Request
```json
{
  "customer": {
    "name": "",
    "email": "",
    "phone": "",
    "message": ""
  },
  "estimate": {
    "level": "level_1",
    "formula_version": "v1",
    "price_version": "v1",
    "raw_input": {},
    "raw_result": {}
  }
}
```

## 3. Admin formula preview

`POST /api/admin/estimator/formula-sets/{id}/preview`

Cho phép preview formula draft chưa publish.

## 4. Import formula JSON

`POST /api/admin/estimator/formula-import`

## 5. Export formula JSON

`GET /api/admin/estimator/formula-sets/{id}/export`

