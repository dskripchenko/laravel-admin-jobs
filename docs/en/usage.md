---
title: Usage
locale: en
status: stable
---

# Usage

Permissions:

- `admin.failed-jobs.view` — see the list
- `admin.failed-jobs.retry` — retry single
- `admin.failed-jobs.forget` — delete

Configure visible queues / connections:

```php
// config/admin-jobs.php
'connections' => ['redis', 'database'],
'queues' => ['default', 'high', 'low'],
```

