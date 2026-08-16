<?php


namespace App\Traits\HelperTraits;


use App\Models\Admin;
use App\Models\Agency;
use App\Models\User;
use Encore\Admin\Show;

trait InfoTrait
{
    public static function getUserShow($id){

        $user = User::find($id);
        if (!$user){
            return null;
        }
        $show = new Show($user);
        $show->setResource ('/admin/users');

        $show->field('id', __('Id'));
        $show->field ('avatar',__('image'))->image ('',200);
        $show->field('name', __('Name'));
        $show->field('nickname', __('NickName'));
        $show->field('flag', __('country'))->image ('',50);
        $show->field('email', __('Email'));
        $show->field('di', __('coins'));
        $show->field('gold', __('silver coins'));
        $show->field('coins', __('diamonds'));
        $show->field('is_host', __('is host'))->using (
            [
                0=>__ ('not host'),
                1=>__('host')
            ]
        );
        $show->field ('agency_id',__ ('agency id'));
        $show->panel()
            ->tools(function ($tools) {
                $tools->disableEdit();
                $tools->disableList();
                $tools->disableDelete();
            });



        return $show;
    }

    public static function getAgencyShow($id){
        $agency = Agency::find($id);
        if (!$agency){
            return null;
        }
        $show = new Show($agency);
        $show->setResource ('/admin/agencies');

        $show->id('ID');
        $show->field('owner_id',__('owner id'));//->link ('/public/admin/auth/users/'.$show->getModel ()->owner_id);
        $show->field('name',__ ('name'));
        $show->field('notice',__ ('notice'));
        $show->field('status',__('status'))->using (
            [
                0=>__ ('unActive'),
                1=>__ ('Active'),
            ]
        );
        $show->field('phone',__ ('phone'));
        $show->field('url',__ ('url'));
        $show->field('img',__ ('image'))->image ('',200);
        $show->field('contents',__ ('contents'));
        $show->panel()
            ->tools(function ($tools) {
                $tools->disableEdit();
                $tools->disableList();
                $tools->disableDelete();
            });

        return $show;
    }

    public static function getAdminShow($id){
        $admin = Admin::find($id);
        if (!$admin){
            return null;
        }
        $show = new Show($admin);
        $show->setResource ('admin/auth/users');
        $show->field('id', 'ID');
        $show->field('avatar', trans('admin.avatar'))->image ('',200);
        $show->field('username', trans('admin.username'));
        $show->field('name', trans('admin.name'));
        $show->field('roles', trans('admin.roles'))->as(function ($roles) {
            return $roles?->pluck('name');
        })->label();
        $show->field('permissions', trans('admin.permissions'))->as(function ($permission) {
            return $permission?->pluck('name');
        })->label();
        $show->panel()
            ->tools(function ($tools) {
                $tools->disableEdit();
                $tools->disableList();
                $tools->disableDelete();
            });

        return $show;
    }


    public static function getusersShow($id){
        $admin = User::find($id);
        if (!$admin){
            return null;
        }
        $show = new Show($admin);
        $show->setResource ('admin/auth/users');
        $show->field('id', 'ID');
        $show->field('avatar', trans('admin.avatar'))->image ('',200);
        $show->field('username', trans('admin.username'));
        $show->field('name', trans('admin.name'));
        $show->field('roles', trans('admin.roles'))->as(function ($roles) {
            return $roles?->pluck('name');
        })->label();
        $show->field('permissions', trans('admin.permissions'))->as(function ($permission) {
            return $permission?->pluck('name');
        })->label();
        $show->panel()
            ->tools(function ($tools) {
                $tools->disableEdit();
                $tools->disableList();
                $tools->disableDelete();
            });

        return $show;
    }
}
