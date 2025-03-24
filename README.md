# Simple Event Manager for Magento 2

Simple event management module for Magento 2 that allows you to create and manage events, display them in an interactive calendar, and enable customer registration.

## Features

### Admin
- Event management grid with filtering and sorting
- Add/Edit event form with the following fields:
  - Name of Event
  - Short Description (for listings and calendar popups)
  - Content (WYSIWYG editor)
  - Event Date
  - Publish Date
  - Category
  - Preview Image (main event image)
  - Multiple Photos (gallery)
  - Enable/Disable Join Form
  - Participation limit (if join form enabled)
  - Enable Countdown

### Frontend
- Event listing page with filtering options
- Interactive event calendar with popup details
- Calendar day view with events for specific dates
- Detailed event page with:
  - Name
  - Event date
  - Countdown (if enabled)
  - Rich content
  - Photo gallery
  - Join Form (if enabled)
    - Name
    - Email
    - Phone

### Widget Support
- Event Calendar Widget - insert a calendar anywhere in your site
- Event List Widget - display upcoming events in any CMS page or block

## Installation

### Manual Installation
1. Create the following directory structure in your Magento installation: `app/code/IDangerous/SimpleEventManager`
2. Extract the module files to this directory
3. Run the following commands from your Magento root:

```bash
bin/magento module:enable IDangerous_SimpleEventManager
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:clean
```

4. Set proper permissions:
```bash
find var generated pub/static pub/media app/etc -type f -exec chmod g+w {} \;
find var generated pub/static pub/media app/etc -type d -exec chmod g+ws {} \;
```

5. Create the required media directories:
```bash
mkdir -p pub/media/idangerous/event/preview
mkdir -p pub/media/idangerous/event/photo
chmod -R 777 pub/media/idangerous
```

### Using Composer
```bash
composer require idangerous/module-simple-event-manager
bin/magento module:enable IDangerous_SimpleEventManager
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:clean
```

## Usage

### Admin
1. Navigate to the admin panel
2. Go to Events > Manage Events
3. Use the "Add New Event" button to create events
4. Fill in the event details and save

### Frontend
- Events listing: `/events`
- Event calendar: `/events/calendar`
- Event calendar for specific date: `/events/calendar/date/{YYYY-MM-DD}`
- Event details: `/events/view/id/{event_id}`

### Using Widgets
1. Go to Content > Widgets > Add Widget
2. Select the widget type:
   - "Event Calendar" - Displays the interactive calendar
   - "Event List" - Shows a list of upcoming events
3. Configure widget settings and placement
4. Save and flush cache

## Troubleshooting

### CSS Not Updating
If CSS changes aren't appearing, try the following:

```bash
php bin/magento cache:clean
php bin/magento setup:static-content:deploy -f
```

For more aggressive clearing:

```bash
rm -rf var/view_preprocessed/* pub/static/* generated/*
php bin/magento setup:static-content:deploy -f
```

### Image Upload Issues
If image uploads aren't working:

1. Check directory permissions:
```bash
chmod -R 777 pub/media/idangerous
```

2. Ensure the directory structure exists:
```bash
mkdir -p pub/media/idangerous/event/preview
mkdir -p pub/media/idangerous/event/photo
```

## Credits and Acknowledgments

The interactive calendar implementation in this module is based on the design by Kalvin Calimag, which was adapted from his CodePen example at: https://codepen.io/kalvincalimag/details/wvLYdLv

## License

This software is licensed under a Custom License.

### Non-Commercial Use
- This software is free for non-commercial use
- You may copy, distribute and modify the software as long as you track changes/dates in source files
- Any modifications must be made available under the same license terms

### Commercial Use
- Commercial use of this software requires explicit permission from the author
- Please contact [Orkan Köylü](orkan.koylu@gmail.com) for commercial licensing inquiries

Copyright (c) 2024 Orkan Köylü. All Rights Reserved.