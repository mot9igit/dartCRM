<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    $dev = MODX_BASE_PATH . 'Extras/dartCRM/';
    /** @var xPDOCacheManager $cache */
    $cache = $modx->getCacheManager();
    if (file_exists($dev) && $cache) {
        if (!is_link($dev . 'assets/components/dartcrm')) {
            $cache->deleteTree(
                $dev . 'assets/components/dartcrm/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_ASSETS_PATH . 'components/dartcrm/', $dev . 'assets/components/dartcrm');
        }
        if (!is_link($dev . 'core/components/dartcrm')) {
            $cache->deleteTree(
                $dev . 'core/components/dartcrm/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_CORE_PATH . 'components/dartcrm/', $dev . 'core/components/dartcrm');
        }
    }
}

return true;