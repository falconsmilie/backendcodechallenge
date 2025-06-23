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
            <a href="?page=<?= $page - 1 ?>&results_per_page=<?= $resultsPerPage ?>">← Previous</a>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&results_per_page=<?= $resultsPerPage ?>" style="margin-left:10px;">Next →</a>
        <?php endif; ?>
    </div>

    <?php
    foreach ($commits as $author => $group):
        ?>
        <section>
            <?php
            if ($group[0]['author_avatar_url'] !== ''):
            ?>
            <div style="height: 96px">
                <img src="<?= $group[0]['author_avatar_url'] ?>"
                     alt="<?= $author ?>"
                     style="float:left; width:64px; height:64px; margin:0 24px 0 0" />
                <?php
            endif;
            ?>
                <h2 style="position: relative; padding-top: 12px">
                    <?php
                    if ($group[0]['author_html_url'] !== ''):
                    ?>
                    <a href="<?= $group[0]['author_html_url']; ?>" target="_blank">
                    <?php
                    endif;
                    ?>
                        <?= htmlspecialchars($author) ?>
                    <?php if($group[0]['author_html_url'] !== ''): ?>
                    </a>
                    <?php endif; ?>
                </h2>
            </div>
            <div style="display: grid; grid-template-columns: 50% 50%; gap: 0.5rem; padding:0; margin:0">
                <?php foreach ($group as $commit): ?>
                    <article style="margin:0;">
                        <div>
                            <div>
                                <a href="<?= $commit['commit_html_url']; ?>" target="_blank">
                                    <?= htmlspecialchars($commit['hash']) ?>
                                </a>
                            </div>
                            <div><?= $commit['commit_date'] ?></div>
                            <div>
                                <a href="https://<?= $commit['provider']?>.com/<?= $commit['owner'].'/'.$commit['repo'] ?>" target="_blank">
                                    <?= htmlspecialchars($commit['owner']) ?>/<?= htmlspecialchars($commit['repo']) ?>
                                </a>
                            </div>
                            <div style="padding: 0 5px 5px 15px">
                                <small style="word-break: break-word"><?= htmlspecialchars($commit['commit_message']) ?></small>
                            </div>
                        </div>

                        <div style="font-size: 8px; overflow: hidden; padding: 12px 8px">
                            <pre style="overflow: hidden; color: yellow">
                                <?= trim(json_encode($commit, JSON_PRETTY_PRINT)) ?>
                            </pre>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php
    endforeach;
    ?>
    <div>
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&results_per_page=<?= $resultsPerPage ?>">← Previous</a>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&results_per_page=<?= $resultsPerPage ?>" style="margin-left:10px;">Next →</a>
        <?php endif; ?>
    </div>
    <?php
}
?>