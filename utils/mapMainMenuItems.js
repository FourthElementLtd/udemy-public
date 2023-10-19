import { v4 as uuid } from 'uuid'

export const mapMainMenuItems = (menuItems) => {
    return menuItems.map((menuItems) => ({
        id: uuid(),
        destination: menuItems.menuItem.destination?.uri,
        label: menuItems.menuItem.label,
        subMenuItems: ( menuItems.items || []).map((subMenuItems) => ({
            id: uuid(),
            destination: subMenuItems.destination?.uri,
            label: subMenuItems.label,
        })),
    }));
};