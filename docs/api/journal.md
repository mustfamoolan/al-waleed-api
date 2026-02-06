# Journal & Accounts API

All routes require authentication (`Bearer Token`).

## Accounts (Tree)

### Get Chart of Accounts
**GET** `/api/accounts`

**Response (200 OK):**
Returns a list of root accounts, each containing a `children_recursive` array with their sub-accounts.

```json
[
    {
        "id": 1,
        "name": "Assets",
        "children_recursive": [
            { "id": 5, "name": "Current Assets", "children_recursive": [...] }
        ]
    }
]
```

### Create Account
**POST** `/api/accounts`

**Parameters:**
- `account_code` (required, unique)
- `name` (required)
- `type` (required: asset, liability, equity, revenue, expense)
- `parent_id` (optional)
- `is_postable` (boolean, default false)

---

## Journal Entries

### List Entries
**GET** `/api/journal-entries`

**Response (200 OK):** Paginated list of journal entries.

### Create Manual Entry
**POST** `/api/journal-entries/manual`

**Parameters:**
- `entry_date` (required, date)
- `description` (required)
- `lines` (required, array of objects):
    - `account_id` (required, int)
    - `debit_amount` (required, numeric)
    - `credit_amount` (required, numeric)

*Note: Total Debit MUST equal Total Credit.*

**Response (201 Created):**
Creates a `draft` entry.

### Post Entry
**POST** `/api/journal-entries/{id}/post`

Changes status from `draft` to `posted`.
**Note:** Only `posted` entries affect actual balances (if implemented).

### Cancel Entry
**POST** `/api/journal-entries/{id}/cancel`

- Can only cancel `posted` entries.
- Changes status to `canceled`.
- **Automatically creates a new Reversal Entry** with swapped Debit/Credit.
