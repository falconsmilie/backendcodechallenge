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

    <div style="padding: 10px 10px 10px 10px">
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
        <article>
            <?php
            if ($group[0]->author_avatar_url):
            ?>
            <div style="height: 96px">
                <img src="<?= $group[0]->author_avatar_url ?>"
                     alt="<?= $author ?>"
                     style="float:left; width:64px; height:64px; margin:0 24px 0 0" />
                    <?php
                endif;
                ?>
                <h2 style="position: relative; padding-top: 12px">
                    <a href="<?= $group[0]->author_html_url; ?>" target="_blank"><?= htmlspecialchars($author) ?></a>
                </h2>
            </div>

            <div>
                <?php foreach ($group as $commit): ?>
                    <div style="float: right; width: 250px; text-align: right; font-size: 4px; margin-top: -120px">
                        <code>
                            <?= var_dump($commit) ?>
                        </code>
                    </div>
                    <div>
                        <a href="<?= $commit->commit_html_url; ?>" target="_blank">
                            <?= htmlspecialchars($commit->hash) ?>
                        </a>
                        (<?= $commit->commit_date ?>)
                    </div>
                    <div style="width: 640px;padding-bottom: 12px">
                        <small><?= htmlspecialchars($commit->commit_message) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
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