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
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0.5rem;">
                <?php foreach ($group as $commit): ?>
                    <div style="box-sizing: border-box; align-self: start; margin: 0; padding: 0.25rem;">
                        <a href="<?= $commit->commit_html_url; ?>" target="_blank">
                            <?= htmlspecialchars($commit->hash) ?>
                        </a>
                        (<?= $commit->commit_date ?>)
                        <div style="padding: 0 5px 5px 15px">

                        <small><?= htmlspecialchars($commit->commit_message) ?></small>
                        </div>
                    </div>

                    <div style="box-sizing: border-box; font-size: 4px; overflow: hidden;">
                        <article>
                            <code style="color:grey">
                                <?= var_dump($commit) ?>
                            </code>
                        </article>
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