<h1>
    Commits by Author
    <?php
    if (count($commits)):
        ?>
        (Page <?= htmlspecialchars($page) ?> of <?= htmlspecialchars($totalPages) ?>)
        <?php
    endif
    ?>
</h1>

<?php
if (! count($commits)) {
    ?>

    <div>There are no results for that request.</div>

    <?php
} else {
    ?>

    <div>
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">← Previous</a>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" style="margin-left:10px;">Next →</a>
        <?php endif; ?>
    </div>

    <?php
    foreach ($commits as $author => $group):
        ?>
        <img src="<?= $group[0]->author_avatar_url ?>"
             alt="<?= $author ?>"
             style="float:left; width:24px; height:24px; padding: 18px 4px" />
        <h2 style="position: relative">
            <a href="<?= $group[0]->author_html_url; ?>" target="_blank"><?= htmlspecialchars($author) ?></a>
        </h2>
        <ul>
            <?php foreach ($group as $commit): ?>
                <li>
                    <a href="<?= $commit->commit_html_url; ?>" target="_blank">
                        <?= htmlspecialchars($commit->hash) ?>
                    </a>
                    (<?= $commit->commit_date ?>)
                </li>
                <li style="width: 640px">
                    <?= htmlspecialchars($commit->commit_message) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php
    endforeach;
    ?>

    <div>
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">← Previous</a>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" style="margin-left:10px;">Next →</a>
        <?php endif; ?>
    </div>
    <?php
}
?>