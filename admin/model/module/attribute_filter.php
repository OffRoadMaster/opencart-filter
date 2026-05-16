<?php
namespace Opencart\Admin\Model\Extension\OpencartFilter\Module;

class AttributeFilter extends \Opencart\System\Engine\Model {
    private const EVENT_CODE = 'opencart_filter_attribute_filter_reindex';

    public function install(): void {
        $this->createTables();
        $this->registerEvents();
        $this->rebuildIndex();
    }

    public function uninstall(): void {
        $this->unregisterEvents();
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "attribute_filter_index`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "attribute_filter_value`");
    }

    public function createTables(): void {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "attribute_filter_index` (
            `product_id` int(11) NOT NULL,
            `category_id` int(11) NOT NULL,
            `attribute_id` int(11) NOT NULL,
            `language_id` int(11) NOT NULL,
            `text` varchar(255) NOT NULL,
            `value_hash` char(32) NOT NULL,
            PRIMARY KEY (`product_id`,`category_id`,`attribute_id`,`language_id`,`value_hash`),
            KEY `category_attribute_language` (`category_id`,`attribute_id`,`language_id`),
            KEY `attribute_value` (`attribute_id`,`language_id`,`value_hash`),
            KEY `product_category` (`product_id`,`category_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "attribute_filter_value` (
            `category_id` int(11) NOT NULL,
            `attribute_id` int(11) NOT NULL,
            `language_id` int(11) NOT NULL,
            `text` varchar(255) NOT NULL,
            `value_hash` char(32) NOT NULL,
            `product_count` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`category_id`,`attribute_id`,`language_id`,`value_hash`),
            KEY `attribute_language` (`attribute_id`,`language_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function rebuildIndex(): void {
        $this->createTables();
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_filter_index`");
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_filter_value`");

        $sql = "SELECT DISTINCT p.product_id, ptc.category_id, pa.attribute_id, pa.language_id, TRIM(pa.text) AS text
            FROM `" . DB_PREFIX . "product` p
            INNER JOIN `" . DB_PREFIX . "product_to_category` ptc ON (ptc.product_id = p.product_id)
            INNER JOIN `" . DB_PREFIX . "product_attribute` pa ON (pa.product_id = p.product_id)
            WHERE p.status = '1'
                AND p.date_available <= NOW()
                AND TRIM(pa.text) <> ''
            UNION
            SELECT DISTINCT p.product_id, 0 AS category_id, pa.attribute_id, pa.language_id, TRIM(pa.text) AS text
            FROM `" . DB_PREFIX . "product` p
            INNER JOIN `" . DB_PREFIX . "product_attribute` pa ON (pa.product_id = p.product_id)
            WHERE p.status = '1'
                AND p.date_available <= NOW()
                AND TRIM(pa.text) <> ''";

        $query = $this->db->query($sql);

        foreach ($query->rows as $row) {
            $value = $this->normaliseValue((string)$row['text']);

            if ($value === '') {
                continue;
            }

            $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "attribute_filter_index` SET
                product_id = '" . (int)$row['product_id'] . "',
                category_id = '" . (int)$row['category_id'] . "',
                attribute_id = '" . (int)$row['attribute_id'] . "',
                language_id = '" . (int)$row['language_id'] . "',
                text = '" . $this->db->escape($value) . "',
                value_hash = '" . $this->db->escape(md5($value)) . "'");
        }

        $this->db->query("INSERT INTO `" . DB_PREFIX . "attribute_filter_value` (category_id, attribute_id, language_id, text, value_hash, product_count)
            SELECT category_id, attribute_id, language_id, MIN(text) AS text, value_hash, COUNT(DISTINCT product_id) AS product_count
            FROM `" . DB_PREFIX . "attribute_filter_index`
            GROUP BY category_id, attribute_id, language_id, value_hash");
    }

    public function getAttributes(): array {
        $query = $this->db->query("SELECT a.attribute_id, ad.name, agd.name AS attribute_group
            FROM `" . DB_PREFIX . "attribute` a
            LEFT JOIN `" . DB_PREFIX . "attribute_description` ad ON (a.attribute_id = ad.attribute_id)
            LEFT JOIN `" . DB_PREFIX . "attribute_group_description` agd ON (a.attribute_group_id = agd.attribute_group_id AND ad.language_id = agd.language_id)
            WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY agd.name, ad.name");

        return $query->rows;
    }

    public function registerEvents(): void {
        $this->load->model('setting/event');
        $this->unregisterEvents();

        $events = [
            'admin/model/catalog/product/addProduct/after',
            'admin/model/catalog/product/editProduct/after',
            'admin/model/catalog/product/deleteProduct/after',
            'admin/model/catalog/category/editCategory/after',
            'admin/model/catalog/attribute/editAttribute/after',
            'admin/model/catalog/attribute/deleteAttribute/after'
        ];

        foreach ($events as $trigger) {
            $this->model_setting_event->addEvent([
                'code' => self::EVENT_CODE,
                'description' => 'Rebuild AJAX attribute filter index',
                'trigger' => $trigger,
                'action' => 'extension/opencart_filter/module/attribute_filter.reindexEvent',
                'status' => true,
                'sort_order' => 0
            ]);
        }
    }

    public function unregisterEvents(): void {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode(self::EVENT_CODE);
    }

    private function normaliseValue(string $value): string {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?: '';

        return trim($value);
    }
}
