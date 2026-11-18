# Payments: how giving works and how to add live Mobile Money

## Today (no API credentials needed)
1. Enter the official MTN number, Airtel number and bank details in Admin > Settings > Giving and donations.
2. A donor pays to those accounts and submits the transaction ID on the Give page. The gift is stored as **pending**.
3. The Finance Officer compares it with the real statement and clicks **Verify payment** (or **Reject**). Only then does the gift count towards a campaign, get a receipt number and trigger the donor's email. Nothing is ever marked successful automatically.
4. Cash and bank deposits are recorded directly under "Record a gift received directly".

## Adding live collection (needs authorisation from the providers)
- **MTN MoMo Collections API** and **Airtel Money Collections API** require a registered business, merchant account and sandbox/production keys issued by each provider. Card payments need a gateway account (for example Flutterwave or Pesapal).
- Suggested integration: add `includes/gateway_<provider>.php` with `request_payment($donationId)` and a callback endpoint `webhooks/<provider>.php` that verifies the provider signature and then calls the existing `verify_donation()`; keep secrets in `config.local.php` (never in the repository) and log every callback.
- Always confirm a payment by querying the provider with the transaction id before verifying, and reconcile weekly against the statement.
