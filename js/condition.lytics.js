(function (Drupal) {
  Drupal.smartContent = Drupal.smartContent || {};
  Drupal.smartContent.plugin = Drupal.smartContent.plugin || {};
  Drupal.smartContent.plugin.Field = Drupal.smartContent.plugin.Field || {};

  Drupal.smartContent.plugin.Field["lytics"] = function (condition) {
    let key = condition.field.pluginId.split(":")[1];

    return new Promise((resolve, reject) => {
      const handleLyticsProfile = function (data) {
        resolve(data?.data?.user);
      };

      if (typeof jstag !== "undefined" && typeof jstag.call === "function") {
        jstag.call("entityReady", handleLyticsProfile);
      } else {
        reject("jstag not found or is not a function");
      }
    })
      .then((profile) => {
        if (profile && profile.hasOwnProperty(key)) {
          return profile[key];
        } else {
          return null;
        }
      })
      .catch((error) => {
        console.error(
          "Error evaluating profile against decision block:",
          error
        );
      });
  };
})(Drupal);
