(function (Drupal) {
  Drupal.smartContent = Drupal.smartContent || {};
  Drupal.smartContent.plugin = Drupal.smartContent.plugin || {};
  Drupal.smartContent.plugin.Field = Drupal.smartContent.plugin.Field || {};

  Drupal.smartContent.plugin.Field["lytics"] = function (condition) {
    // debug mode can be activated by setting a localStorage item
    const debugMode = localStorage.getItem("lytics_smart_content_debug");
    let debug = false;

    if (debugMode && debugMode !== "false") {
      debug = true;
      console.log("Lytics Smart Content Debug is enabled.");
    }

    const splitValue = function (payload) {
      return payload.split(":")[1];
    };

    // {
    //   "field": "user_attributes".
    //   "type": "key_value",
    //   "settings": {
    //     "negate": "0",
    //     "op": "equals",
    //     "key": "donation_type",
    //     "value": "one-time"
    //   }
    // }
    const getValue = function (condition, profile) {
      switch (condition.type) {
        case "array_textfield":
          switch (condition.field) {
            case "_segments":
              return profile?._segments ?? profile?.segments ?? [];
            default:
              return profile?.[condition?.field] ?? [];
          }
        case "boolean":
          return profile && Boolean(profile[condition?.field]);
        case "number":
          return profile?.[condition?.field] ?? null;
        case "select":
          break;
        case "textfield":
          return profile?.[condition?.field] ?? null;
        case "key_value":
          const field = profile?.[condition?.field];
          const key = condition?.settings?.key;
          return field?.[key] ?? null;
        default:
          if (debug) {
            console.warn("Unknown condition type: ", condition.type);
          }
          return null;
      }
    };

    const prepCondition = function (condition) {
      const field = splitValue(condition?.field?.pluginId);
      const type = splitValue(condition?.field?.type);
      const settings = condition?.settings;

      const formattedCondition = {
        field: field,
        type: type,
        settings: settings,
      };

      return formattedCondition;
    };

    return new Promise((resolve, reject) => {
      const handleLyticsProfile = function (data) {
        resolve(data?.data?.user);
      };

      if (typeof jstag !== "undefined" && typeof jstag.call === "function") {
        jstag.call("entityReady", handleLyticsProfile);
      } else {
        reject("Lytics Jstag not found or is not a function.");
      }
    })
      .then((profile) => {
        const cleanCondition = prepCondition(condition);
        const value = getValue(cleanCondition, profile);

        if (debug) {
          console.log("Lytics Smart Content Debug: ", {
            profile: profile,
            condition: cleanCondition,
            value: value,
          });
        }

        if (!value) {
          if (debug) {
            console.warn("No value found for condition: ", cleanCondition);
          }
          return null;
        }

        return value;
      })
      .catch((error) => {
        if (debug) {
          console.error(
            "Error evaluating profile against decision block:",
            error
          );
        }
      });
  };
})(Drupal);
