#[repr(C)]
#[derive(Clone, Copy)]
struct MediaBox {
    x: f64,
    y: f64,
    width: f64,
    height: f64,
}

const FIT_COVER: i32 = 0;
const FIT_CONTAIN: i32 = 1;
const FIT_FILL: i32 = 2;

fn compute_media_box(
    container_width: f64,
    container_height: f64,
    media_width: f64,
    media_height: f64,
    fit: i32,
) -> MediaBox {
    if container_width <= 0.0 || container_height <= 0.0 || media_width <= 0.0 || media_height <= 0.0 {
        return MediaBox {
            x: 0.0,
            y: 0.0,
            width: container_width.max(0.0),
            height: container_height.max(0.0),
        };
    }

    if fit == FIT_FILL {
        return MediaBox {
            x: 0.0,
            y: 0.0,
            width: container_width,
            height: container_height,
        };
    }

    let container_ratio = container_width / container_height;
    let media_ratio = media_width / media_height;
    let scale_by_width = match fit {
        FIT_CONTAIN => media_ratio > container_ratio,
        FIT_COVER => media_ratio < container_ratio,
        _ => media_ratio < container_ratio,
    };

    let (width, height) = if scale_by_width {
        let width = container_width;
        (width, width / media_ratio)
    } else {
        let height = container_height;
        (height * media_ratio, height)
    };

    MediaBox {
        x: (container_width - width) / 2.0,
        y: (container_height - height) / 2.0,
        width,
        height,
    }
}

#[no_mangle]
pub extern "C" fn media_box_x(
    container_width: f64,
    container_height: f64,
    media_width: f64,
    media_height: f64,
    fit: i32,
) -> f64 {
    compute_media_box(container_width, container_height, media_width, media_height, fit).x
}

#[no_mangle]
pub extern "C" fn media_box_y(
    container_width: f64,
    container_height: f64,
    media_width: f64,
    media_height: f64,
    fit: i32,
) -> f64 {
    compute_media_box(container_width, container_height, media_width, media_height, fit).y
}

#[no_mangle]
pub extern "C" fn media_box_width(
    container_width: f64,
    container_height: f64,
    media_width: f64,
    media_height: f64,
    fit: i32,
) -> f64 {
    compute_media_box(container_width, container_height, media_width, media_height, fit).width
}

#[no_mangle]
pub extern "C" fn media_box_height(
    container_width: f64,
    container_height: f64,
    media_width: f64,
    media_height: f64,
    fit: i32,
) -> f64 {
    compute_media_box(container_width, container_height, media_width, media_height, fit).height
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn contain_keeps_media_inside_container() {
        let box_ = compute_media_box(1920.0, 1080.0, 1080.0, 1080.0, FIT_CONTAIN);

        assert_eq!(box_.width, 1080.0);
        assert_eq!(box_.height, 1080.0);
        assert_eq!(box_.x, 420.0);
        assert_eq!(box_.y, 0.0);
    }

    #[test]
    fn cover_fills_container() {
        let box_ = compute_media_box(1920.0, 1080.0, 1080.0, 1080.0, FIT_COVER);

        assert_eq!(box_.width, 1920.0);
        assert_eq!(box_.height, 1920.0);
        assert_eq!(box_.x, 0.0);
        assert_eq!(box_.y, -420.0);
    }
}
