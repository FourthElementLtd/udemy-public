import { getTextAlign } from "utils/fonts";
import { relativeToAbsoluteUrls } from "utils/relativeToAbsoluteURLs";

export const Paragraph = ({textAlign = "left", content, textColor}) => {
    return <p 
    className={`max-w-5xl my-5 mx-auto ${getTextAlign(textAlign)} `}
    style = {{ color: textColor }}
    dangerouslySetInnerHTML={{__html: relativeToAbsoluteUrls(content)}} />;
};