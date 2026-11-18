# What Meta allows (as of November 2026)

Source: Meta's documentation for the Instagram platform (Private replies, Webhooks setup, Messaging API), retrieved 16 and 17 November 2026, and the maker's first installation. Meta can change this. What is uncertain is said.

## Certain, from the documentation
- **One private reply per comment**, up to 7 days after the comment (`POST /<instagram-id>/messages` with `recipient.comment_id`).
- **A public reply** under a comment is always possible (`POST /<comment-id>/replies`).
- **Lengths:** a message without buttons at most 1000 bytes; the text above buttons at most 640 characters; a button text at most 20 characters.
- **The account must be professional and public.** The account's own comments also come in; Jensiechat skips them.
- **A system user token does not expire.** A token from the app dashboard expires after 60 days.
- **Without App Review an app has about 200 calls per hour.** Jensiechat reads the usage Meta reports in each answer and checks less often when it rises.

## Seen at the first installation, not promised by Meta
- The private reply with a button, the public reply and click counting worked without App Review, also for an account without a role on the app, while the app was not published.
- Comment notifications (webhook) only arrived without App Review for accounts with a role on the app. The documentation says Advanced Access is needed for these notifications. That is why Jensiechat checks by itself, every 15 to 60 seconds.
- Meta's dashboard says live data requires App Review. In practice it worked for the own account. If Meta enforces this, App Review will also be needed for the basic version.

## What is never possible
- Messaging someone who did not comment.
- More than one private reply per comment. If another tool (such as ManyChat) already answered the same comment, Meta refuses Jensiechat's message.
- Seeing whether someone follows you.
