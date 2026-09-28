# Cloudforms 0.1.3

Embed a form from a Nextcloud. Developed by Liam Perlaki.

A form that somebody builds in Nextcloud Forms, shown as a form of your own website: the questions
become real fields, an answer is sent to the cloud by the web server, and the answers stay where
they were before, in the cloud. On paper the form prints as the sheet it replaces.

## How to install an extension

[Download ZIP file](https://github.com/pfadfinder26/yellow-cloudforms/archive/refs/heads/main.zip) and copy it into your `system/extensions` folder. [Learn more about extensions](https://github.com/annaesvensson/yellow-update).

## How to embed a form

Share a form in Nextcloud Forms, "Copy link", and paste it into a page:

    [form https://cloud.example.org/apps/forms/s/TOKEN]

`CloudformsUrl` in the system settings holds the cloud, and a form of it, so a page can ask for the
form of the website with `[form]` alone. The share must be open for everybody with the link and it
must accept answers without a login.

The extension reads the page of the share, which carries the questions, and keeps a copy for
`CloudformsCacheTime` seconds, an hour by default. A visit does not wait for the cloud, and a cloud
that cannot be reached falls back to the last copy.

**Sending:** the form posts to your own web server, which sends the answers to the cloud. It works
without JavaScript. A request from another website is refused, and a field that people do not see
catches the simplest robots. Afterwards the page it was sent from is shown again, at the same
address as before: what the cloud answered travels in a cookie that lives a minute and is cleared
when the page says it.

A list of answers that are all short carries a class of its own, `cloudform-options-short`, so a
theme can put "yes" and "no" in one line.

**Questions:** short and long text, a date, a time, a date with time, a choice from a list, several
choices and one choice out of several become the fields you would expect. What a short answer is
checked for in the cloud is carried along: an address becomes an email field, a number a number
field, a phone number a phone field, an expression of its own the pattern of the field, so the
browser says what is wrong before anything is sent. A question that carries a name for filling in
by itself passes that on as well. Anything else becomes a
line of text. A file upload is not offered, a form with one is better opened in the cloud, the link
below the form does that.

**Labels:** `CloudformsLabelSubmit`, `CloudformsLabelDone`, `CloudformsLabelFailed`,
`CloudformsLabelRequired` and `CloudformsLabelOpen` say what the form says, in the language of the
website.

**Printing:** the form is plain HTML, `.cloudform`, with `.cloudform-question`, `.cloudform-label`
and the fields inside it, so a theme can print it as a sheet to fill in by hand. A browser cannot
make a PDF with fields that can be typed into, it can only print what the page shows.

**Data protection:** the answers are sent by your web server, so the visitors never talk to the
cloud themselves and the cloud never sees their address. The server does not keep the answers.
