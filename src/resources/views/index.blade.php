@extends('layouts.app')

@section('title', 'Barocco | интернет-магазин модной обуви и кожгалантерии')

@section('content')

    {{ Banner::getIndexMain() }}

    <div class="col-md-12 index-links-block">
        @include('partials.index.links')
    </div>

    <div class="col-md-12 my-3">
        @includeWhen(isset($simpleSliders[0]), 'partials.index.simple-slider', [
            'simpleSlider' => $simpleSliders[0] ?? null,
        ])
    </div>

    <div class="col-md-12 my-4">
        @include('partials.index.imidj-slider')
    </div>

    <div class="col-md-12 my-3">
        @includeWhen(isset($simpleSliders[1]), 'partials.index.simple-slider', [
            'simpleSlider' => $simpleSliders[1] ?? null,
        ])
    </div>

    <div class="col-md-12 my-3">
        @includeWhen(isset($simpleSliders[2]), 'partials.index.simple-slider', [
            'simpleSlider' => $simpleSliders[2] ?? null,
        ])
    </div>

    {{ Banner::getIndexBottom() }}

    {{-- wrapper close --}}
    </div>
    <div class="row my-5">
        <div class="col-12 bg-danger py-5">
            @include('partials.index.subscribe')
        </div>
    </div>
    <div class="row wrapper justify-content-center">
        {{-- wrapper open --}}

        <div class="col-12 my-3">
            @include('includes.instagram-block')
        </div>

        <div class="col-12 text-justify my-5">
            BAROCCO.BY - ведущий интернет магазин по продаже обуви из натуральной кожи и замши в
            Беларуси.<br>
            В нашем интернет магазине представлены только качественные модели обуви, которые отвечают
            современным тенденциям моды.<br>
            <ul>
                <li>Мы работаем с 2015 года</li>
                <li>Гарантия на всю продукцию</li>
                <li>Широкий размерный ряд</li>
                <li>100% оригинальные бренды</li>
            </ul>
            <br>
            BAROCCO.BY - единственный официальный интернет-магазин бренда BAROCCO в Беларуси, России и
            Казахстане. БАРОККО - это итальянский дизайн воплощенный в изделиях из натуральных
            материалов, таких как кожа, мех и замша.<br>
            В каталоге Вы найдете женские коллекции обуви согласно последним веяниям моды.<br>
            Обувь BAROCCO идеальна в носке и подойдёт каждому, так как она подобрана с учетом
            особенностей строения женской ступни.<br>
            <br>
            Также BAROCCO.BY является официальным поставщиком именитых обувных брендов VITACCI, Basconi,
            Sasha Fabiani. Благодаря многолетнему сотрудничеству с производителями у нас лучшие цены в
            Беларуси.<br>
            <br>
            Мы предлагаем широкий ассортимент обуви для каждого сезона и случая:<br>
            <ul>
                <li>для лета босоножки, сандалии и сабо</li>
                <li>для демисезона и зимы: ботильоны, ботинки, сапоги и ботфорты</li>
                <li>спортивные и повседневные кроссовки, слипоны и кеды</li>
                <li>офисные туфли и лоферы</li>
                <li>вечерние модели туфель, ботильон и босоножек для юбилеев, свадеб, свиданий и др.
                    торжественных случаев</li>
            </ul>
            <br>
            Барокко бай - это не только ассортимент, но и сервис.<br>
            Для Беларуси доступна рассрочка, курьерская доставка и примерка.<br>
            В Россию возможна доставка до отделения СДЭК или EMS<br>
            <br>
            У нас можно купить обувь из натуральных материалов по приемлемым ценам.<br>
            Покупайте качественную обувь - быстро и надежно с BAROCCO.BY!
        </div>
    @endsection
