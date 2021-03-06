<?php

$param = array();

$sql = " FROM `ipv4_mac` AS M, `ports` AS P, `devices` AS D WHERE M.port_id = P.port_id AND P.device_id = D.device_id ";

if (isset($_POST['searchby']) && $_POST['searchby'] == "ip") {
    $sql .= " AND `ipv4_address` LIKE ?";
    $param = array("%".trim($_POST['address'])."%");
} elseif (isset($_POST['searchby']) && $_POST['searchby'] == "mac") {
    $sql .= " AND `mac_address` LIKE ?";
    $param = array("%".str_replace(array(':', ' ', '-', '.', '0x'),'',mres($_POST['address']))."%");
}

if (is_numeric($_POST['device_id'])) {
    $sql  .= " AND P.device_id = ?";
    $param[] = $_POST['device_id'];
}

$count_sql = "SELECT COUNT(`M`.`port_id`) $sql";

$total = dbFetchCell($count_sql,$param);
if (empty($total)) {
    $total = 0;
}

if (!isset($sort) || empty($sort)) {
    $sort = '`hostname` ASC';
}

$sql .= " ORDER BY $sort";

if (isset($current)) {
    $limit_low = ($current * $rowCount) - ($rowCount);
    $limit_high = $rowCount;
}

if ($rowCount != -1) {
    $sql .= " LIMIT $limit_low,$limit_high";
}

$sql = "SELECT *,`P`.`ifDescr` AS `interface` $sql";

foreach (dbFetchRows($sql, $param) as $entry) {
    if (!$ignore) {

        if ($entry['ifInErrors'] > 0 || $entry['ifOutErrors'] > 0) {
            $error_img = generate_port_link($entry,"<img src='images/16/chart_curve_error.png' alt='Interface Errors' border=0>",errors);
        } else {
            $error_img = "";
        }

    $arp_host = dbFetchRow("SELECT * FROM ipv4_addresses AS A, ports AS I, devices AS D WHERE A.ipv4_address = ? AND I.port_id = A.port_id AND D.device_id = I.device_id", array($entry['ipv4_address']));
    if ($arp_host) {
        $arp_name = generate_device_link($arp_host);
    } else {
        unset($arp_name);
    }
    if ($arp_host) {
        $arp_if = generate_port_link($arp_host);
    } else {
        unset($arp_if);
    }
    if ($arp_host['device_id'] == $entry['device_id']) {
        $arp_name = "Localhost";
    }
    if ($arp_host['port_id'] == $entry['port_id']) {
        $arp_if = "Local port";
    }
    $response[] = array('mac_address'=>formatMac($entry['mac_address']),
                        'ipv4_address'=>$entry['ipv4_address'],
                        'hostname'=>generate_device_link($entry),
                        'interface'=>generate_port_link($entry, makeshortif(fixifname($entry['ifDescr']))) . ' ' . $error_img,
                        'remote_device'=>$arp_name,
                        'remote_interface'=>$arp_if);
    }
    unset($ignore);
}

$output = array('current'=>$current,'rowCount'=>$rowCount,'rows'=>$response,'total'=>$total);
echo _json_encode($output);
