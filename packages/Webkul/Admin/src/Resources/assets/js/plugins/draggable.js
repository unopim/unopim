import Draggable from "vuedraggable";

export default {
    install: (app) => {
        /**
         * Global component registration;
         */
        app.component("Draggable", Draggable);
    },
};
