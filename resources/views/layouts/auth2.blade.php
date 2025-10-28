<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale(), false); ?>">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="<?php echo e(csrf_token(), false); ?>">

    <title><?php echo $__env->yieldContent('title'); ?> - <?php echo e(config('app.name', 'POS'), false); ?></title>

    <?php echo $__env->make('layouts.partials.css', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('layouts.partials.extracss_auth', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!-- Support for older IE versions -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <script src="https://www.google.com/recaptcha/api.js"></script>
</head>

<body class="pace-done">
    <?php $request = app('Illuminate\Http\Request'); ?>

    <?php if(session('status') && session('status.success')): ?>
        <input type="hidden" id="status_span" data-status="<?php echo e(session('status.success'), false); ?>" data-msg="<?php echo e(session('status.msg'), false); ?>">
    <?php endif; ?>

    <nav>
        <ul class="navauth">
           
            <li>
                <?php echo $__env->make('layouts.partials.language_btn', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php if(Route::has('repair-status')): ?>
                    <a class="tw-font-medium tw-text-sm md:tw-text-base hover:text-primary"
                       href="<?php echo e(action([\Modules\Repair\Http\Controllers\CustomerRepairStatusController::class, 'index']), false); ?>">
                        <?php echo app('translator')->get('repair::lang.repair_status'); ?>
                    </a>
                <?php endif; ?>
            </li>
            <li>
                <img src="<?php echo e(asset('img/logo-small.png'), false); ?>" alt="logo" class="tw-rounded-full tw-object-fill" />
            </li>
            <?php if($request->segment(1) != 'login'): ?>
            <li>
               
                
                    <a class="tw-font-medium tw-text-sm md:tw-text-base hover:text-primary"
                       href="<?php echo e(action([\App\Http\Controllers\Auth\LoginController::class, 'login']), false); ?><?php echo e(!empty(request()->lang) ? '?lang=' . request()->lang : '', false); ?>">
                       <?php echo e(__('business.sign_in'), false); ?>

                    </a>
              
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <?php echo $__env->yieldContent('content'); ?>

    <?php echo $__env->make('layouts.partials.javascripts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <script src="<?php echo e(asset('js/login.js?v=' . $asset_v), false); ?>"></script>
    <?php echo $__env->yieldContent('javascript'); ?>

    <script>
        $(document).ready(function() {
            $('.select2_register').select2();
        });
    </script>
</body>
</html>
<?php /**PATH C:\Apache24\htdocs\orbitpos\resources\views/layouts/auth2.blade.php ENDPATH**/ ?>