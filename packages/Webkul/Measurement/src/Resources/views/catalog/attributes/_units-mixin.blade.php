@pushOnce('scripts')
    <script type="module">
        /**
         * Shared family/unit behaviour for the attribute create and edit forms:
         * per-family unit fetching (cached), mixed into both so it can't drift.
         */
        window.measurementUnitsMixin = {
            data() {
                return {
                    unitsList: null,
                    unitsCache: {},
                };
            },

            methods: {
                /**
                 * Normalise a multiselect value (JSON string or object) to the
                 * selected family. Returns `undefined` when a string cannot be
                 * parsed so the caller can abort, and `null` when nothing is
                 * selected.
                 */
                parseSelectedFamily(value) {
                    if (
                        typeof value === 'string'
                        && value.trim() !== ''
                    ) {
                        try {
                            return JSON.parse(value);
                        } catch (e) {
                            return undefined;
                        }
                    }

                    if (
                        value
                        && typeof value === 'object'
                    ) {
                        return value;
                    }

                    return null;
                },

                /**
                 * Units belong to one family and are fetched per selection, so the
                 * page does not carry the units of every family in the catalogue.
                 */
                async fetchUnits(familyCode) {
                    if (! familyCode) {
                        this.unitsList = [];

                        return;
                    }

                    if (this.unitsCache[familyCode]) {
                        this.unitsList = this.unitsCache[familyCode];

                        return;
                    }

                    try {
                        const response = await this.$axios.get(this.familyUnitsUrl, {
                            params: { family: familyCode },
                        });

                        this.unitsCache[familyCode] = response.data.units || [];
                        this.unitsList = this.unitsCache[familyCode];
                    } catch (error) {
                        console.error('Error loading measurement units:', error);

                        this.unitsList = [];
                    }
                },
            },
        };
    </script>
@endPushOnce
