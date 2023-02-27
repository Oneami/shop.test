<?php

namespace App\Admin\Controllers\Users;

use App\Models\Country;
use App\Models\User\Group;
use App\Models\User\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Validator;

class UserController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'User';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());

        $grid->column('first_name', 'Имя');
        $grid->column('last_name', 'Фамилия');
        $grid->column('patronymic_name', 'Отчество');
        $grid->column('email', 'Email');
        $grid->column('phone', 'Телефон');
        $grid->column('orders', 'Сумма покупок')->display(fn () => $this->completedOrdersCost() . ' руб.');
        $grid->column('group.name', 'Группа');
        $grid->column('reviews_count', 'Кол-во отзывов');
        $grid->column('addresses', 'Адрес')->display(fn ($addresses) => $addresses[0]['address'] ?? null);
        $grid->column('created_at', 'Дата регистрации');

        $grid->model()->withCount('reviews')->orderBy('id', 'desc');
        $grid->paginate(50);

        $grid->filter(function (Filter $filter) {
            $filter->like('first_name', 'Имя');
            $filter->like('last_name', 'Фамилия');
            $filter->like('patronymic_name', 'Отчество');
            $filter->like('phone', 'Телефон');
            $filter->equal('group_id', 'Группа')->select(Group::query()->pluck('name', 'id'));
            $filter->like('email', 'Email');
            $filter->like('addresses.city', 'Город');
            $filter->like('addresses.address', 'Адрес');
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param  mixed  $id
     * @return Show
     */
    protected function detail($id)
    {
        return back();
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User());

        $form->tab('Основная ин-ия', function ($form) {
            $form->text('first_name', 'Имя')->required();
            $form->text('last_name', 'Фамилия')->required();
            $form->text('patronymic_name', 'Отчество');
            $form->email('email', 'Email');
            $form->phone('phone', 'Телефон')->required();
            $form->date('birth_date', 'Дата рождения')->default(date('Y-m-d'));
            $form->select('group_id', 'Группа')->options(Group::query()->pluck('name', 'id'))->required();

            $form->hasMany('addresses', 'Адреса', function (Form\NestedForm $form) {
                $form->select('country_id', 'Страна')->options(Country::query()->pluck('name', 'id'));
                $form->text('city', 'Город');
                $form->textarea('address', 'Адрес');
            });

            $form->hasMany('reviews', 'Отзывы', function (Form\NestedForm $form) {
                $form->textarea('text', 'Отзыв')->readonly();
            });
        });

        $form->tab('Паспортные данные', function ($form) {
            $form->text('passport.passport_number', 'Номер паспорта');
            $form->text('passport.series', 'Серия паспорта');
            $form->text('passport.issued_by', 'Кем выдан');
            $form->date('passport.issued_date', 'Когда выдан');
            $form->text('passport.personal_number', 'Личный номер');
        });

        $form->submitted(function (Form $form) {

            $requestData = request()->all();
            $passportData = $requestData['passport'] ?? [];
            $emptyPassport = empty(array_filter($passportData, function ($a) {
                return $a !== null;
            }));
            if ($emptyPassport) {
                $form->ignore('passport');
            } else {
                request()->validate([
                    'passport.passport_number' => ['required'],
                    'passport.series' => ['required'],
                    'passport.issued_by' => ['required'],
                    'passport.issued_date' => ['required'],
                    'passport.personal_number' => ['required'],
                ], [
                    'passport.passport_number.required' => 'Поле номер паспорта обязательно для заполнения.',
                    'passport.series.required' => ' Поле серия паспорта обязательно для заполнения.',
                    'passport.issued_by.required' => ' Поле кем выдан обязательно для заполнения.',
                    'passport.issued_date.required' => ' Поле когда выдан обязательно для заполнения.',
                    'passport.personal_number.required' => ' Поле личный номер обязательно для заполнения.',
                ]);
            }
        });
        return $form;
    }
}
