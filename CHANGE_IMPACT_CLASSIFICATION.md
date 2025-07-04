# 🔁 Change Impact Classification Guideline

Use this guide to classify changes in the upgrade documentation.

---

## 🔴 High Impact Changes

These are **breaking or critical changes** that are very likely to affect plugin behavior or require updates.

**Examples:**
- Removing or renaming public methods, classes, or properties.
- Changing method signatures that plugins override like Exporters or Importers functions.
- Modifying interfaces, abstract classes, or services commonly extended.
- Changing data structures, service container bindings, or API responses.
- Altering formats or behavior that plugins rely on.

---

## 🟠 Medium Impact Changes

These are **non-breaking changes** that may modify behavior or add functionality.

**Examples:**
- Adding new optional methods or services.
- Internal refactoring without changing public interfaces.
- Adding features without affecting previous functionalities like a seperate feature.
- Enhancing or extending logic.

---

## 🟢 Low Impact Changes

These are **safe changes** that are cosmetic or internal. No plugin or changes should be needed.

**Examples:**
- Code formatting, linting, or comment updates.
- Documentation or README improvements.
- Minor bug fixes that do not alter expected behavior.
- Logging additions.

---

## 📝 Notes

- If you're unsure, default to a **higher impact level** to be cautious.
- Treat changes as **High Impact** if they affect any method or class likely to be extended or overridden by plugins.
