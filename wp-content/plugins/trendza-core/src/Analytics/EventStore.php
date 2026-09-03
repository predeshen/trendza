<?php
namespace Trendza\Analytics;

final class EventStore {
    public const TABLE_SUFFIX = 'trendza_events';
    public static function table(): string { global $wpdb; return $wpdb->prefix . self::TABLE_SUFFIX; }
    public static function install(): void {
        global $wpdb; $table=self::table(); $charset=$wpdb->get_charset_collate(); require_once ABSPATH.'wp-admin/includes/upgrade.php';
        dbDelta("CREATE TABLE {$table} (id bigint(20) unsigned NOT NULL AUTO_INCREMENT, product_id bigint(20) unsigned NOT NULL DEFAULT 0, event_type varchar(32) NOT NULL, occurred_at datetime NOT NULL, session_hash char(64) NOT NULL DEFAULT '', metadata longtext NULL, PRIMARY KEY (id), KEY product_event_time (product_id,event_type,occurred_at), KEY event_time (event_type,occurred_at), KEY session_time (session_hash,occurred_at)) {$charset};");
    }
    public static function record(int $productId,string $eventType,string $sessionHash='',array $metadata=[]): bool {
        global $wpdb; $allowed=['view','search','add_to_cart','begin_checkout','purchase']; if(!in_array($eventType,$allowed,true)) return false;
        return false!==$wpdb->insert(self::table(),['product_id'=>max(0,$productId),'event_type'=>$eventType,'occurred_at'=>current_time('mysql',true),'session_hash'=>substr(hash('sha256',$sessionHash),0,64),'metadata'=>$metadata?wp_json_encode($metadata):null],['%d','%s','%s','%s','%s']);
    }
    public static function count(int $productId,string $eventType,int $hours): int { global $wpdb; $since=gmdate('Y-m-d H:i:s',time()-max(1,$hours)*HOUR_IN_SECONDS); return (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.self::table().' WHERE product_id=%d AND event_type=%s AND occurred_at >= %s',$productId,$eventType,$since)); }
    public static function countAll(int $hours=24): int { global $wpdb; $since=gmdate('Y-m-d H:i:s',time()-max(1,$hours)*HOUR_IN_SECONDS); return (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.self::table().' WHERE occurred_at >= %s',$since)); }
    public static function countsByType(int $hours=24): array { global $wpdb; $since=gmdate('Y-m-d H:i:s',time()-max(1,$hours)*HOUR_IN_SECONDS); $rows=$wpdb->get_results($wpdb->prepare('SELECT event_type, COUNT(*) AS total FROM '.self::table().' WHERE occurred_at >= %s GROUP BY event_type',$since),ARRAY_A); $counts=[]; foreach((array)$rows as $row)$counts[sanitize_key($row['event_type'])]=(int)$row['total']; return $counts; }
    public static function prune(int $days=90): void { global $wpdb; $before=gmdate('Y-m-d H:i:s',time()-max(1,$days)*DAY_IN_SECONDS); $wpdb->query($wpdb->prepare('DELETE FROM '.self::table().' WHERE occurred_at < %s',$before)); }
}
