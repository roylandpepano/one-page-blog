// assets/admin.js - admin page JS (theme toggle, toasts, AJAX handlers)
(function () {
   // Theme toggle
   const body = document.body;
   const KEY = "onepageblog:theme";
   function applyTheme(mode, btn) {
      if (mode === "dark") body.classList.add("dark-mode");
      else body.classList.remove("dark-mode");
      if (btn) btn.textContent = mode === "dark" ? "☀️" : "🌙";
   }
   let saved = null;
   try {
      saved = localStorage.getItem(KEY);
   } catch (e) {}
   if (!saved) {
      const prefersDark =
         window.matchMedia &&
         window.matchMedia("(prefers-color-scheme: dark)").matches;
      saved = prefersDark ? "dark" : "light";
   }
   // find toggle button if present
   const btn = document.getElementById("themeToggle");
   applyTheme(saved, btn);
   if (btn) {
      btn.addEventListener("click", function () {
         const isDark = body.classList.contains("dark-mode");
         const next = isDark ? "light" : "dark";
         applyTheme(next, btn);
         try {
            localStorage.setItem(KEY, next);
         } catch (e) {}
      });
   }

   // Show notice param toasts
   (function () {
      function getParam(name) {
         const params = new URLSearchParams(window.location.search);
         return params.get(name);
      }
      const n = getParam("notice");
      if (n) {
         if (n === "edited")
            iziToast.success({
               title: "Saved",
               message: "Comment updated",
               position: "topRight",
            });
         else if (n === "deleted")
            iziToast.success({
               title: "Deleted",
               message: "Comment removed",
               position: "topRight",
            });
         else
            iziToast.info({ title: "Info", message: n, position: "topRight" });
         const url = new URL(window.location.href);
         url.searchParams.delete("notice");
         window.history.replaceState({}, document.title, url.toString());
      }
   })();

   // AJAX save/delete for admin comments
   (function () {
      function showError(msg) {
         iziToast.error({ title: "Error", message: msg, position: "topRight" });
      }
      function showSuccess(msg) {
         iziToast.success({
            title: "Success",
            message: msg,
            position: "topRight",
         });
      }

      $(document).ready(function () {
         $(document).on("click", ".btn-save", function () {
            const form = $(this).closest("form");
            const data = form.serialize();
            const btn = $(this);
            btn.prop("disabled", true);
            $.post(
               "admin.php",
               data,
               function (res) {
                  if (res && res.success) {
                     showSuccess(res.message || "Saved");
                  } else showError((res && res.message) || "Save failed");
               },
               "json"
            )
               .fail(function () {
                  showError("Request failed");
               })
               .always(function () {
                  btn.prop("disabled", false);
               });
         });

         $(document).on("click", ".btn-delete", function () {
            const id = $(this).data("id");
            if (!confirm("Delete this comment?")) return;
            const form = $(this).closest("form");
            // Build minimal payload: csrf_token, ajax flag, and delete_id
            const csrf = form.find('input[name="csrf_token"]').val();
            const data = {
               delete_id: id,
               csrf_token: csrf,
               ajax: 1,
            };
            const btn = $(this);
            btn.prop("disabled", true);
            $.post(
               "admin.php",
               data,
               function (res) {
                  if (res && res.success) {
                     showSuccess(res.message || "Deleted");
                     form.slideUp(200, function () {
                        $(this).remove();
                     });
                  } else showError((res && res.message) || "Delete failed");
               },
               "json"
            )
               .fail(function () {
                  showError("Request failed");
               })
               .always(function () {
                  btn.prop("disabled", false);
               });
         });
      });
   })();
})();
