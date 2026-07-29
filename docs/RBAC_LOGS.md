# RBAC Deny Logging

- Αρχείο: `storage/logs/rbac.log`
- Format: ISO8601 time, reason, JSON context.
- Παράδειγμα:

```
2025-08-21T17:05:00+03:00 DENY missing_permission {"uid":1,"required":"jobs.edit.any"}
2025-08-21T17:05:15+03:00 DENY owner_or_any_failed {"uid":2,"perm_own":"jobs.edit.own","perm_any":"jobs.edit.any","owner":false}
```

## Τύποι Deny Events

- `missing_permission`: Λείπει συγκεκριμένο permission
- `missing_any`: Λείπει οποιοδήποτε από λίστα permissions
- `missing_all`: Λείπει τουλάχιστον ένα από όλα τα απαιτούμενα permissions
- `owner_or_any_failed`: Αποτυχία στον έλεγχο ownership ή global permission

## Monitoring

- Συνίσταται περιοδικό rotate/cleanup στο production.
- Χρήση logrotate ή παρόμοιου εργαλείου για διαχείριση μεγέθους αρχείων.
- Monitoring για ύποπτα patterns (πολλαπλές αποτυχίες από τον ίδιο χρήστη).

## Context Fields

- `uid`: User ID που προσπάθησε την πρόσβαση
- `required`: Απαιτούμενο permission
- `required_any`: Λίστα permissions (οποιοδήποτε)
- `required_all`: Λίστα permissions (όλα)
- `perm_own`: Own permission στον έλεγχο ownership
- `perm_any`: Any permission στον έλεγχο ownership
- `owner`: Boolean αποτέλεσμα ownership check
- `missing`: Συγκεκριμένο permission που λείπει
