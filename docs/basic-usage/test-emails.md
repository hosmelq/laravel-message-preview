---
title: "Test Emails and Exports"
description: "Send a message to an inbox, or copy and download its HTML and .eml file."
weight: 5
---

The Actions menu, the three dots at the end of the toolbar, sends the open message as a test email and exports it. Both use the variant and locale you are viewing.

## Send a test email

Choose Send a test email in the Actions menu, or press `E`. Enter an address in To and choose Send. The address replaces every recipient, cc and bcc included, while the subject, sender, headers, and attachments stay as rendered.

When `config/mail.php` defines more than one mailer, the Mailer field chooses the one to send through. It starts on the default mailer, and the preview remembers the address and the mailer in this browser.

To keep the message on your machine, send it through a mailer that points at Mailpit or another local mail catcher. To see it in a mail client, send it to a real inbox. The preview hands the message straight to the mailer's transport, so no mail events are dispatched. If the transport fails, the preview shows its error.

## Export the message

The rest of the Actions menu exports the message:

| Item | What it does |
| --- | --- |
| Open in a browser tab | Opens the HTML or plain-text body you are viewing in a new tab. `O` does the same. |
| Copy HTML | Copies the HTML body. |
| Copy plain text | Copies the plain-text body. |
| Download HTML | Downloads the HTML body as an `.html` file. |
| Download EML | Downloads the whole message as an `.eml` file, which desktop mail clients open. |

Opening and downloading render the message again on the server. The copies use the message already on screen. Copying needs HTTPS or `localhost`, so the copy items are disabled on a plain `http://` host such as `my-app.test`. Open in a browser tab is disabled while Source is shown.
