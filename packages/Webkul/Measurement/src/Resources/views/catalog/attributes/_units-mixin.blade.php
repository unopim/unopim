@pushOnce('scripts')
    <script type="module">
        window.unopim = window.unopim || {};

        window.unopim.measurementUnits = {
            data() {
                return {
                    unitsList: null,
                    unitsCache: {},
                };
            },

            methods: {
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
