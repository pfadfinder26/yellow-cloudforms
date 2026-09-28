# Cloudforms 0.2.2

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
line of text. A question that wants a file says what to do with it instead, `CloudformsLabelFile`, printing it or
sending it by email, because the file belongs to the cloud and never to this website. When such a question is mandatory the form is not shown at all,
only the link, since an answer without the file would be refused.

**What the form says:** the title and the description of the form stand above the fields, and the
description of a single question above that field. A form that is closed, expired or full shows no
fields, only a note and the link into the cloud. When a form has a message of its own for afterwards
it is shown instead of the usual thanks.

**A confirmation by email:** Nextcloud Forms can send one, "Confirmation email" in the settings of
the form, together with the question whose answer holds the address. Nothing is needed here for
that: the answers are the ones the cloud would have received anyway, so the cloud sends its email as
usual. The question for the address is best made a mandatory one that is checked as an address.

After an answer has arrived a button offers to fill the form in again, for the second child of a
family.

**Labels:** `CloudformsLabelSubmit`, `CloudformsLabelDone`, `CloudformsLabelAgain`,
`CloudformsLabelFailed`, `CloudformsLabelRequired`, `CloudformsLabelClosed`, `CloudformsLabelFile`
and `CloudformsLabelOpen` say what the form says, in the language of the
website.

**Printing:** the form is plain HTML, `.cloudform`, with `.cloudform-question`, `.cloudform-label`
and the fields inside it, so a theme can print it as a sheet to fill in by hand. A browser cannot
make a PDF with fields that can be typed into, it can only print what the page shows.

**Data protection:** the answers are sent by your web server, so the visitors never talk to the
cloud themselves and the cloud never sees their address. The server does not keep the answers.
