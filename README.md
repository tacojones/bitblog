# bitblog

A simple, file-based blog system built with PHP and Markdown.

## Features

📝 Markdown post support
🔍 Full-text search
📱 Responsive pagination
⚡ Fast & lightweight (single file)
📦 No database required

## Installation

1. Upload files to your PHP-enabled server
2. Install [Parsedown](https://github.com/erusev/parsedown) (required)
3. Create Markdown posts in `/posts/` directory

## Configuration

Edit these variables at the top of `index.php`:
```php
$postsPerPage = 5;          // Posts per page
$postsDir = 'posts/';       // Post storage directory
$blogName = "bitblog"; // Blog title  
$blogDescription = "A blog about nothing"; // SEO description
$blogStyle = "style.css";   // Custom CSS file
```

## Post Format

Create .md files in /posts/ with front matter:

```md
---
title: My First Post
date: 2023-01-01
---

Post content in **Markdown**

<!--more--> 
Excerpt separator
```
