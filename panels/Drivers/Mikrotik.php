<?php

class MikrotikPanelDriver extends BasePanelDriver
{
    public function create(array $vars)
    {
        $Get_Data_Panel = $vars['Get_Data_Panel'];
        $Get_Data_Product = $vars['Get_Data_Product'];
        $code_product = $vars['code_product'];
        $usernameC = $vars['usernameC'];
        $Output = $vars['Output'];
        $password = bin2hex(random_bytes(6));
        // panel_protocol stores proxies as a JSON array (["profile"]); the
        // MikroTik user-manager profile is a single string.
        $name_group = $Get_Data_Panel['proxies'];
        $decoded_group = is_string($name_group) ? json_decode($name_group, true) : null;
        if (is_array($decoded_group) && count($decoded_group) === 1 && is_string($decoded_group[0])) {
            $name_group = $decoded_group[0];
        }
        if (is_array($Get_Data_Product) && ($Get_Data_Product['inbounds'] ?? null) != null) {
            $name_group = $Get_Data_Product['inbounds'];
        } elseif ($code_product == "usertest") {
            $name_group = "usertest";
        }
        $data_Output = addUser_mikrotik($Get_Data_Panel['name_panel'], $usernameC, $password, $name_group);
        if (isset($data_Output['error'])) {
            $Output['status'] = 'Unsuccessful';
            $Output['msg'] = $data_Output['error'] ?? 'panel error';
        } else {
            $Output['status'] = 'successful';
            $Output['username'] = $usernameC;
            $Output['subscription_url'] = $password;
            $Output['configs'] = [];
        }
        return $Output;
    }

    public function read(array $vars)
    {
        $Get_Data_Panel = $vars['Get_Data_Panel'];
        $username = $vars['username'];
        $Output = $vars['Output'];
        $UsernameResponse = GetUsermikrotik($Get_Data_Panel['name_panel'], $username);
        $UsernameData = $UsernameResponse[0] ?? null;
        if (!is_array($UsernameData)) {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => $UsernameResponse['message'] ?? 'user not found on panel'
            );
        } else {
            $invocie = select("invoice", "*", "username", $username, "select");
            if (!is_array($invocie)) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => 'invoice not found'
                );
            }
            $traffic_get = GetUsermikrotik_volume($Get_Data_Panel['name_panel'], $UsernameData['.id']);
            $used_traffic = ($traffic_get['total-upload'] ?? 0) + ($traffic_get['total-download'] ?? 0);
            $data_limit = ($invocie['Volume'] ?? 0) * pow(1024, 3);
            $expire = strtotime((string) ($invocie['time_sell'] ?? '')) + ((int) ($invocie['Service_time'] ?? 0) * 86400);
            $UsernameData['enable'] = "active";
            $Output = array(
                'status' => $UsernameData['enable'],
                'username' => $invocie['username'],
                'data_limit' => $data_limit,
                'expire' => $expire,
                'online_at' => null,
                'used_traffic' => $used_traffic,
                'links' => [],
                'subscription_url' => $UsernameData['password'],
                'sub_updated_at' => null,
                'sub_last_user_agent' => null,
            );
        }
        return $Output;
    }

    public function remove(array $vars)
    {
        $Get_Data_Panel = $vars['Get_Data_Panel'];
        $username = $vars['username'];
        $Output = $vars['Output'];
        $UsernameResponse = GetUsermikrotik($Get_Data_Panel['name_panel'], $username);
        $UsernameData = $UsernameResponse[0] ?? null;
        if (!is_array($UsernameData)) {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => $UsernameResponse['message'] ?? 'user not found on panel'
            );
        } else {
            $removeResponse = deleteUser_mikrotik($Get_Data_Panel['name_panel'], $UsernameData['.id']);
            if (isset($removeResponse['error'])) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $removeResponse['message'] ?? $removeResponse['error']
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'username' => $username,
                );
            }
        }
        return $Output;
    }
}
