<?php
namespace Opencart\Catalog\Model\Extension\AJAXaf\Module;

class AttributeFilter extends \Opencart\System\Engine\Model {
    public function getFilterGroups(int $category_id, int $language_id, array $attribute_ids = []): array {
        $attribute_sql = '';

        if ($attribute_ids) {
            $attribute_sql = " AND afv.attribute_id IN (" . implode(',', array_map('intval', $attribute_ids)) . ")";
        }

        $query = $this->db->query("SELECT afv.attribute_id, afv.text, afv.value_hash, afv.product_count, ad.name AS attribute_name, agd.name AS attribute_group
            FROM `" . DB_PREFIX . "attribute_filter_value` afv
            INNER JOIN `" . DB_PREFIX . "attribute_description` ad ON (ad.attribute_id = afv.attribute_id AND ad.language_id = afv.language_id)
            LEFT JOIN `" . DB_PREFIX . "attribute` a ON (a.attribute_id = afv.attribute_id)
            LEFT JOIN `" . DB_PREFIX . "attribute_group_description` agd ON (agd.attribute_group_id = a.attribute_group_id AND agd.language_id = afv.language_id)
            WHERE afv.category_id = '" . (int)$category_id . "'
                AND afv.language_id = '" . (int)$language_id . "'" . $attribute_sql . "
            ORDER BY agd.name, ad.name, afv.text");

        $groups = [];

        foreach ($query->rows as $row) {
            $attribute_id = (int)$row['attribute_id'];

            if (!isset($groups[$attribute_id])) {
                $groups[$attribute_id] = [
                    'attribute_id' => $attribute_id,
                    'name' => $row['attribute_name'],
                    'group' => $row['attribute_group'],
                    'values' => []
                ];
            }

            $groups[$attribute_id]['values'][] = [
                'text' => $row['text'],
                'value_hash' => $row['value_hash'],
                'product_count' => (int)$row['product_count']
            ];
        }

        return array_values($groups);
    }

    public function getProductIds(int $category_id, int $language_id, array $filters): array {
        $filters = $this->normaliseFilters($filters);

        if (!$filters) {
            $query = $this->db->query("SELECT DISTINCT p.product_id
                FROM `" . DB_PREFIX . "product` p
                INNER JOIN `" . DB_PREFIX . "product_to_category` ptc ON (ptc.product_id = p.product_id)
                WHERE ptc.category_id = '" . (int)$category_id . "'
                    AND p.status = '1'
                    AND p.date_available <= NOW()
                ORDER BY p.sort_order, LCASE(p.model)");

            return array_map('intval', array_column($query->rows, 'product_id'));
        }

        $joins = [];
        $index = 0;

        foreach ($filters as $attribute_id => $hashes) {
            $alias = 'afi' . $index;
            $joins[] = "INNER JOIN `" . DB_PREFIX . "attribute_filter_index` " . $alias . " ON (" . $alias . ".product_id = p.product_id
                AND " . $alias . ".category_id = '" . (int)$category_id . "'
                AND " . $alias . ".language_id = '" . (int)$language_id . "'
                AND " . $alias . ".attribute_id = '" . (int)$attribute_id . "'
                AND " . $alias . ".value_hash IN ('" . implode("','", array_map([$this->db, 'escape'], $hashes)) . "'))";
            $index++;
        }

        $query = $this->db->query("SELECT DISTINCT p.product_id
            FROM `" . DB_PREFIX . "product` p
            " . implode("\n", $joins) . "
            WHERE p.status = '1'
                AND p.date_available <= NOW()
            ORDER BY p.sort_order, LCASE(p.model)");

        return array_map('intval', array_column($query->rows, 'product_id'));
    }

    private function normaliseFilters(array $filters): array {
        $normalised = [];

        foreach ($filters as $attribute_id => $values) {
            $attribute_id = (int)$attribute_id;
            $values = is_array($values) ? $values : [$values];
            $hashes = [];

            foreach ($values as $value) {
                $value = preg_replace('/[^a-f0-9]/i', '', (string)$value) ?: '';

                if (strlen($value) === 32) {
                    $hashes[] = strtolower($value);
                }
            }

            if ($attribute_id && $hashes) {
                $normalised[$attribute_id] = array_values(array_unique($hashes));
            }
        }

        return $normalised;
    }
}
