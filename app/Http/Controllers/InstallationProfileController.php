<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InstallationProfileController extends Controller
{
    /**
     * 显示 Installation 用户 Profile
     */
    public function index()
    {
        // 假数据，后续可以替换成从 DB 拉取
        $user = [
            'full_name'      => 'Installation Operator',
            'email'          => 'installation@colossal360.com.my',
            'role'           => 'Operations Installation',
            'contact_number' => '017-222-3333',
            'status'         => 'Active',
            'avatar'         => null,
        ];

        return view('installation.profile', compact('user'));
    }
}
