import { faBath, faBed, faCar, faDog } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import numeral from "numeral";

export const PropertyFeatures = ({price,bedrooms,bathrooms,hasParking,petFriendly}) => {
    return (
        <div className="max-w-lg mx-auto my-10 bg-white text-slate-900 p-5 text-center">
            <div className="grid grid-cols-2 mb-4 gap-y-5">
                <div>
                    <FontAwesomeIcon icon={faBed} /> {bedrooms} bedrooms
                </div>
                <div>
                    <FontAwesomeIcon icon={faBath} /> {bathrooms} bathrooms
                </div>
                <div>
                    {!! hasParking &&
                        <>
                            <FontAwesomeIcon icon={faCar} /> {hasParking} has parking
                        </>
                    }       
                </div>
                <div>
                {!! petFriendly &&
                        <>
                            <FontAwesomeIcon icon={faDog} /> {petFriendly} is pet friendly
                        </>
                 }   
                </div>
            </div>
            <h3 className="text-5xl font-bold">
                £{numeral(price).format("0,0")}
            </h3>
        </div>
    );
};