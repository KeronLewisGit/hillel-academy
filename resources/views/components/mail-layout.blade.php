<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>{{ $title ?? 'Hillel Academy' }}</title></head>
<body style="margin:0;background:#f3f6fb;font-family:'Plus Jakarta Sans',Segoe UI,Arial,sans-serif;color:#14213d;font-size:16px;line-height:1.6">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f6fb;padding:24px 12px"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:6px;border-top:6px solid #e8a317">
<tr><td style="padding:28px 32px 8px"><p style="margin:0;font-family:Georgia,serif;font-size:22px;color:#14213d">Hillel Academy</p><p style="margin:2px 0 0;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#4d5a73">Admissions Office · Kingston, Jamaica</p></td></tr>
<tr><td style="padding:16px 32px 28px">{{ $slot }}</td></tr>
<tr><td style="padding:16px 32px 28px;border-top:1px solid #e2e7f0;font-size:13px;color:#4d5a73">Hillel Academy · {{ hillel('address.street') }}, {{ hillel('address.city') }} · {{ setting('phone_admissions') }} · {{ setting('email_admissions') }}</td></tr>
</table></td></tr></table>
</body>
</html>
