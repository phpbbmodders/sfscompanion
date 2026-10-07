# TODO

Not covered yet:

- **Honeypot on the login form** - `login_body.html` defines no template event in core to inject a field into. Event request prepared at [docs/events/login_body_buttons_before.txt](events/login_body_buttons_before.txt).
- **Letting restricted accounts PM staff only** - restricted accounts should still be able to reach admins and moderators, just not regular members, but no core event exposes the recipient list before a PM is sent. Event request prepared at [docs/events/ucp_pm_compose_modify_parse_after_address_list.txt](events/ucp_pm_compose_modify_parse_after_address_list.txt). Until that lands, restricted accounts can still PM anyone; only posting is blocked.
