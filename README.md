# @escalated-dev/plugin-community

Public community forums with categories, topics, replies, voting, moderation, and ticket-to-topic conversion.

## Features

- Multi-category forum structure
- Topic creation with optional pre-moderation
- Threaded replies with solution marking
- Voting on topics and replies
- Ticket-to-topic conversion (adds "Convert to Community Topic" action)
- Public forum page + admin management page
- Real-time broadcast on new posts

## Hooks

| Type | Hook | Description |
|------|------|-------------|
| Action | `community.post.created` | Broadcasts new post to the community channel |
| Filter | `ticket.actions` | Adds "Convert to Community Topic" action |

## Endpoints

| Method | Path | Capability |
|--------|------|-----------|
| GET/POST | `/categories` | public / `manage_settings` |
| GET | `/topics` | public |
| POST | `/topics` | authenticated |
| GET | `/topics/:id` | public |
| POST | `/topics/:id/replies` | authenticated |
| POST | `/topics/:id/vote` | authenticated |
| POST | `/topics/:id/convert-from-ticket` | `manage_tickets` |
| GET/POST | `/settings` | `manage_settings` |
