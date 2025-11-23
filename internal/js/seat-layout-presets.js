/**
 * Seat Layout Presets
 * This file contains predefined seat layout configurations for different cinema room types
 */

const SeatLayoutPresets = {
    /**
     * Small cinema layout - single section
     * - 8 rows x 12 columns
     * - VIP seats in the middle
     * - Premium seats in front center
     */
    small: {
        rows: 8,
        columns: 12,
        sections: 1,
        pattern: function(layout) {
            // Tất cả ghế đều là regular
            for (let i = 0; i < layout.length; i++) {
                for (let j = 0; j < layout[0].length; j++) {
                    layout[i][j].type = 'regular';
                }
            }
            return layout;
        }
    },
    
    /**
     * Medium cinema layout - two sections with aisle
     * - 12 rows x 16 columns
     * - Center aisle
     * - VIP seats in the middle rows
     * - Premium seats in front center
     */
    medium: {
        rows: 12,
        columns: 16,
        sections: 2,
        pattern: function(layout) {
            for (let i = 0; i < layout.length; i++) {
                for (let j = 0; j < layout[0].length; j++) {
                    layout[i][j].type = 'regular';
                }
            }
            return layout;
        }
    },
    
    /**
     * Large cinema layout - three sections with aisles
     * - 15 rows x 20 columns
     * - Two aisles creating three sections
     * - VIP seats in middle rows
     * - Premium seats in front center
     * - Sweet-box seats in strategic locations
     */
    large: {
        rows: 15,
        columns: 20,
        sections: 3,
        pattern: function(layout) {
            for (let i = 0; i < layout.length; i++) {
                for (let j = 0; j < layout[0].length; j++) {
                    layout[i][j].type = 'regular';
                }
            }
            return layout;
        }
    }
};

export default SeatLayoutPresets;
