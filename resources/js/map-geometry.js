export function isPointInsideGeometry(point, geometry) {
    if (!geometry) {
        return false;
    }

    if (geometry.type === 'Polygon') {
        return isPointInsidePolygon(point, geometry.coordinates);
    }

    if (geometry.type === 'MultiPolygon') {
        return geometry.coordinates.some((polygon) => isPointInsidePolygon(point, polygon));
    }

    return false;
}

function isPointInsidePolygon(point, rings) {
    if (!rings?.length || !isPointInRing(point, rings[0])) {
        return false;
    }

    return !rings.slice(1).some((ring) => (
        isPointInRing(point, ring) && !isPointOnRing(point, ring)
    ));
}

function isPointInRing([longitude, latitude], ring) {
    let isInside = false;

    for (let index = 0, previousIndex = ring.length - 1; index < ring.length; previousIndex = index, index++) {
        const [currentLongitude, currentLatitude] = ring[index];
        const [previousLongitude, previousLatitude] = ring[previousIndex];

        if (isPointOnSegment(longitude, latitude, currentLongitude, currentLatitude, previousLongitude, previousLatitude)) {
            return true;
        }

        const crossesLatitude = (currentLatitude > latitude) !== (previousLatitude > latitude);
        const intersectionLongitude = (
            (previousLongitude - currentLongitude)
            * (latitude - currentLatitude)
            / (previousLatitude - currentLatitude)
            + currentLongitude
        );

        if (crossesLatitude && longitude < intersectionLongitude) {
            isInside = !isInside;
        }
    }

    return isInside;
}

function isPointOnRing([longitude, latitude], ring) {
    return ring.some(([currentLongitude, currentLatitude], index) => {
        const previous = ring[index === 0 ? ring.length - 1 : index - 1];

        return isPointOnSegment(
            longitude,
            latitude,
            currentLongitude,
            currentLatitude,
            previous[0],
            previous[1],
        );
    });
}

function isPointOnSegment(longitude, latitude, startLongitude, startLatitude, endLongitude, endLatitude) {
    const crossProduct = (latitude - startLatitude) * (endLongitude - startLongitude)
        - (longitude - startLongitude) * (endLatitude - startLatitude);

    if (Math.abs(crossProduct) > 1e-10) {
        return false;
    }

    return longitude >= Math.min(startLongitude, endLongitude) - 1e-10
        && longitude <= Math.max(startLongitude, endLongitude) + 1e-10
        && latitude >= Math.min(startLatitude, endLatitude) - 1e-10
        && latitude <= Math.max(startLatitude, endLatitude) + 1e-10;
}
