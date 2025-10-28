<?php $__env->startSection('title', config('app.name', 'Orbit')); ?>
<?php $request = app('Illuminate\Http\Request'); ?>
<?php $__env->startSection('content'); ?>

<div class="container">
      <div class="row">
         <span class="text1" id="helloText">Hello!</span>
         <span class="text2" id="text2" style="color: #b1b1b1;">Manage your business Now</span>
         <!-- Same language here -->
         <a class="btn-primary"
                    href="<?php echo e(action([\App\Http\Controllers\Auth\LoginController::class, 'login']), false); ?><?php echo e(!empty(request()->lang) ? '?lang=' . request()->lang : '', false); ?>">
                    <?php echo e(__('business.start_now'), false); ?>

                 </a>
      </div>
   </div>

   
   <script>
      // Add translations for both text1 and text2
      const translations = [
         {
            text1: "Hello!",
            text2: "Manage your business Now"
         },
         {
            text1: "Bonjour!",
            text2: "Gérez votre entreprise maintenant"
         },
         {
            text1: "مرحباً!",
            text2: "إدارة أعمالك الآن"
         },
         {
            text1: "¡Hola!",
            text2: "Gestiona tu negocio ahora"
         }
      ];

      let currentIndex = 0;

      function changeText() {
         // Reset the animation for text1
         const textElement = document.getElementById("helloText");
         textElement.classList.remove("text1"); // Remove animation class
         void textElement.offsetWidth; // Trigger reflow (to restart the animation)
         textElement.classList.add("text1"); // Add the animation class again

         // Change the text content for both text1 and text2
         const { text1, text2 } = translations[currentIndex];
         textElement.textContent = text1;
         document.getElementById("text2").textContent = text2;

         currentIndex = (currentIndex + 1) % translations.length;
      }

      // Change text every 2 seconds while preserving animation
      setInterval(changeText, 2000);
   </script>
<?php $__env->stopSection(); ?>
         
<?php echo $__env->make('layouts.auth2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
