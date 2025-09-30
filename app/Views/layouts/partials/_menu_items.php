<?php foreach ($items as $item): ?>
    <?php if ($item['is_dropdown'] && !empty($item['children'])): ?>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php if ($item['icon']): ?>
                    <i class="bi bi-<?= esc($item['icon']) ?>"></i>
                <?php endif; ?>
                <?= esc($item['title']) ?>
            </a>
            <ul class="dropdown-menu">
                <?php foreach ($item['children'] as $child): ?>
                    <li>
                        <a class="dropdown-item" href="<?= esc($child['url']) ?>">
                            <?php if ($child['icon']): ?>
                                <i class="bi bi-<?= esc($child['icon']) ?>"></i>
                            <?php endif; ?>
                            <?= esc($child['title']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </li>
    <?php else: ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= esc($item['url']) ?>">
                <?php if ($item['icon']): ?>
                    <i class="bi bi-<?= esc($item['icon']) ?>"></i>
                <?php endif; ?>
                <?= esc($item['title']) ?>
            </a>
        </li>
    <?php endif; ?>
<?php endforeach; ?>