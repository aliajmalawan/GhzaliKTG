# Official Email Setup – info@ghazaliktg.com

## What the website does
- Contact form (`contact.php`) and admission form (`admission.php`) send two emails each:
  a notification to the school (`MAIL_ADMIN_TO`, default info@ghazaliktg.com) and a confirmation to the visitor.
- Visitor's address is set as **Reply-To** on the school notification, so hitting *Reply* in webmail answers the visitor.
- All mail goes through `includes/mailer.php` using authenticated SMTP. No passwords are in code.

## 1. Create the mailbox (cPanel)
1. cPanel → **Email Accounts** → Create `info@ghazaliktg.com` (if it doesn't already exist) and set a strong password.
2. Click **Connect Devices** next to it. Note the **Outgoing (SMTP) server**, port and SSL/TLS option shown there.
   Don't guess – use exactly what cPanel shows (often `mail.ghazaliktg.com`, port 465 SSL, but confirm).

## 2. Give the website the credentials
Copy `includes/mail.env.example` to `includes/mail.env` and fill in:

| Key | Value |
|-----|-------|
| MAIL_HOST | SMTP host from Connect Devices |
| MAIL_PORT | 465 (ssl) or 587 (tls) |
| MAIL_ENCRYPTION | `ssl`, `tls` or `none` |
| MAIL_USERNAME | info@ghazaliktg.com (full address) |
| MAIL_PASSWORD | the mailbox password |
| MAIL_FROM_ADDRESS | info@ghazaliktg.com |
| MAIL_ADMIN_TO | where notifications are delivered |

`mail.env` is blocked from web access (`includes/.htaccess`) and git-ignored. Real server environment variables with the
same names take priority over the file. Never upload `mail.env` anywhere public or commit it.

## 3. Verify
- SSH/Terminal: `php send_test_mail.php you@example.com` → prints `SENT`, or `FAILED` with the reason in `includes/mail.log`.
- Then submit the contact form on the live site and check info@ghazaliktg.com inbox plus the confirmation at the sender address.
- Common failures: wrong host/port/encryption (connect error), wrong password (`AUTH password` in log),
  host blocks outbound SMTP ports (ask hosting support; try the other port).
- For best inbox delivery ask hosting to confirm SPF and DKIM are enabled (cPanel → Email Deliverability).

## 4. How admins send and receive
- **Receive:** website enquiries arrive in the info@ghazaliktg.com inbox; they are also stored in the admin panel (Contacts / Admissions).
- **Webmail:** `https://ghazaliktg.com/webmail` (or `:2096`) – log in with the full address and mailbox password.
- **Send:** compose in Webmail, or add the account to Outlook/Gmail/phone using the Connect Devices settings
  (IMAP/POP3 incoming + SMTP outgoing, username = full address).
- Replying to a website enquiry from the inbox goes straight to the visitor (Reply-To).
- Keep the password with the school administration; change it in cPanel if staff change, then update `mail.env`.
