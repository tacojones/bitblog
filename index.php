<?php
require_once 'Parsedown.php';

// Configuration
$postsPerPage = 5;          // Number of posts per page
$postsDir = 'posts/';       // Directory to store markdown posts
$blogName = "Road to Ruin";  // Name of your blog
$blogDescription = "A blog about nothing";  // Blog description for SEO
$blogStyle = "style.css";   // CSS file for styling
$blogFavicon = "favicon.ico"; // Path to favicon
$parsedown = new Parsedown();

// Security measures
if (!is_dir($postsDir)) mkdir($postsDir, 0755);
if (!file_exists($postsDir.'index.html')) file_put_contents($postsDir.'index.html', '');

function getPosts() {
    global $postsDir;
    $posts = [];
    $files = glob($postsDir.'*.md');
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $slug = basename($file, '.md');
        
        // Parse front matter
        $frontMatter = [];
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)/s', $content, $matches)) {
            $frontLines = explode("\n", $matches[1]);
            foreach ($frontLines as $line) {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);
                    $frontMatter[$key] = $value;
                }
            }
            $content = $matches[2];
        }
        
        // Validate required fields
        if (!isset($frontMatter['title']) || !isset($frontMatter['date'])) {
            continue;
        }
        
        $posts[] = [
            'title' => $frontMatter['title'],
            'date' => date('F j, Y', strtotime($frontMatter['date'])),
            'timestamp' => strtotime($frontMatter['date']),
            'content' => $content,
            'slug' => $slug
        ];
    }
    
    // Sort by timestamp descending
    usort($posts, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    return $posts;
}


// Handle current page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$searchQuery = isset($_GET['s']) ? trim($_GET['s']) : null;
$selectedPost = isset($_GET['post']) ? basename($_GET['post']) : null;

// Get posts or search results
$allPosts = getPosts();
if ($searchQuery) {
    $keywords = preg_split('/\s+/', $searchQuery);
    $allPosts = array_filter($allPosts, function($post) use ($keywords) {
        $text = strip_tags(strtolower($post['content']));
        foreach ($keywords as $keyword) {
            if (strpos($text, strtolower($keyword)) === false) return false;
        }
        return true;
    });
}

// Pagination
// Pagination
$totalPosts = count($allPosts);
$totalPages = ceil($totalPosts / $postsPerPage);
$posts = array_slice($allPosts, ($page-1)*$postsPerPage, $postsPerPage);

$currentPage = $page;
$displayPages = [];

if ($totalPages > 0) {
    $displayPages[] = 1; // Always show first page

    $doubleTruncation = ($currentPage > 6) && ($currentPage < $totalPages - 5);

    if ($doubleTruncation) {
        // Add left ellipsis if needed
        if ($currentPage - 2 > 2) {
            $displayPages[] = '...';
        }

        // Add middle pages around current
        for ($i = $currentPage - 2; $i <= $currentPage + 2; $i++) {
            if ($i > 1 && $i < $totalPages) {
                $displayPages[] = $i;
            }
        }

        // Add right ellipsis if needed
        if ($currentPage + 2 < $totalPages - 1) {
            $displayPages[] = '...';
        }
    } else {
        // Single truncation logic
        $middleStart = max(2, $currentPage - 2);
        $middleEnd = min($totalPages - 1, $currentPage + 2);

        if ($middleStart > 2) {
            $displayPages[] = '...';
        }

        for ($i = $middleStart; $i <= $middleEnd; $i++) {
            $displayPages[] = $i;
        }

        if ($middleEnd < $totalPages - 1) {
            $displayPages[] = '...';
        }
    }

    if ($totalPages > 1) {
        $displayPages[] = $totalPages; // Always show last page
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($blogDescription) ?>">
    <title><?= htmlspecialchars($blogName) ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($blogStyle) ?>">
    <link rel="icon" href="<?= htmlspecialchars($blogFavicon) ?>" type="image/x-icon">
</head>
<body>
    <div class="container">
        <header>
         <h1 class="site-title"><a href="/"><?= htmlspecialchars($blogName) ?></a></h1>
         <img src="https://i.imgur.com/fVSfqAV.png" alt="User Avatar" class="user-avatar">
         <nav class="menu-items">
            <a href="/">Home</a>
            <a href="#">About</a>
            <a href="#">Contact</a>
            <a href="#" id="search-toggle">Search</a>
            <div class="search-container">
                <form action="" method="get">
                    <input type="text" name="s" class="search-input" placeholder="Search posts..." 
                           value="<?= htmlspecialchars($searchQuery ?? '') ?>">
                </form>
            </div>
        </nav>
        </header>
        <main>
        <?php if ($selectedPost): ?>
            <?php foreach ($allPosts as $post): ?>
                <?php if ($post['slug'] === $selectedPost): ?>
                    <div class="post">
                        <div class="post-title"><h2><?= htmlspecialchars($post['title']) ?></h2></div>
                        <div class="date"><?= $post['date'] ?></div>
                        <div class="post-content">
                            <?= $parsedown->text($post['content']) ?>
                        </div>
                        <a href="/">← Back</a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="post-title"><h2><a href="?post=<?= $post['slug'] ?>"><?= htmlspecialchars($post['title']) ?></a></h2></div>
                    <div class="date"><?= $post['date'] ?></div>
                    <div class="post-content">
    <?php
        $content = $post['content'];
        $excerpt = strpos($content, '<!--more-->') 
            ? substr($content, 0, strpos($content, '<!--more-->'))
            : $content;
        echo $parsedown->text($excerpt);
    ?>
    <?php if (strpos($content, '<!--more-->')): ?>
        <a href="?post=<?= $post['slug'] ?>">Read More</a>
    <?php endif; ?>
</div>
                </div>
            <?php endforeach; ?>
    </main>
    <div class="pagination">
    <?php foreach ($displayPages as $p): ?>
        <?php if ($p === '...'): ?>
            <span class="ellipsis">...</span>
        <?php else: ?>
            <a href="?page=<?= $p ?><?= $searchQuery ? '&s='.urlencode($searchQuery) : '' ?>" 
               <?= $p == $currentPage ? 'style="color:#fff;"' : '' ?>>
                <?= $p ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
        <?php endif; ?>

        <footer>
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($blogName) ?>. All rights reserved.</p>
        </footer>
    </div>
    <script>
        const searchToggle = document.getElementById('search-toggle');
        const searchContainer = document.querySelector('.search-container');
        const searchInput = document.querySelector('.search-input');

        // Toggle search container
        searchToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            searchContainer.classList.toggle('active');
            if (searchContainer.classList.contains('active')) {
                searchInput.focus();
            }
        });

        // Close search when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchContainer.contains(e.target) && e.target !== searchToggle) {
                searchContainer.classList.remove('active');
            }
        });

        // Prevent closing when clicking inside search container
        searchContainer.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    </script>
</body>
</html>