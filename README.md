# Escalated Plugin: Community

Public community forums for Escalated with categories, topics, replies, voting, moderation, and ticket-to-topic conversion. Provides a customer-facing forum and an admin management interface.

## Features

- Multi-category forum structure with a default "General" category on first activation
- Topic creation with optional pre-moderation (approve before publish)
- Threaded replies with solution marking
- Voting on topics
- Ticket-to-topic conversion via ticket action menu
- Public forum page and admin management page
- Real-time broadcast notifications on new posts

## Configuration

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `title` | text | No | Community forum title. Defaults to `Community Forum`. |
| `allow_anonymous_viewing` | boolean | No | Allow unauthenticated users to view topics. Defaults to `true`. |
| `require_auth_to_post` | boolean | No | Require authentication to create topics and replies. Defaults to `true`. |
| `moderation_mode` | select | No | `post_first` (moderate later) or `pre_moderated` (approve before publish). Defaults to `post_first`. |
| `enable_voting` | boolean | No | Enable voting on topics. Defaults to `true`. |

## Admin Pages

- **community** — Public-facing community forum browser.
- **admin/community** — Admin interface for managing categories, topics, and moderation.

## Hooks

### Actions
- `community.post.created` — Broadcasts new post notifications to the community channel.

### Filters
- `ticket.actions` — Adds a "Convert to Community Topic" action to the ticket action menu.

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/categories` | List all forum categories. |
| POST | `/categories` | Create a new category. |
| PUT | `/categories/:id` | Update a category. |
| DELETE | `/categories/:id` | Delete a category. |
| GET | `/topics` | List topics, optionally filtered by category_id. |
| POST | `/topics` | Create a new topic. |
| GET | `/topics/:id` | Get a topic with its replies. |
| POST | `/topics/:id/replies` | Add a reply to a topic. |
| POST | `/topics/:id/vote` | Upvote a topic. |
| POST | `/topics/:id/convert-from-ticket` | Convert a ticket into a community topic. |
| GET | `/settings` | Get plugin configuration. |
| POST | `/settings` | Save plugin configuration. |

## Installation

```bash
npm install @escalated-dev/plugin-community
```

## License

MIT
